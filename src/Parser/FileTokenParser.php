<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Parser;

use Donquixote\QuickAttributes\Builder\Arguments\ArgumentsBuilderInterface;
use Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilder_NoOp;
use Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilderInterface;
use Donquixote\QuickAttributes\Builder\ClassBody\ClassBodyBuilderInterface;
use Donquixote\QuickAttributes\Builder\File\FileBuilderInterface;
use Donquixote\QuickAttributes\Builder\Parameters\ParametersBuilderInterface;
use Donquixote\QuickAttributes\Builder\Value\ArrayBuilderInterface;
use Donquixote\QuickAttributes\Builder\Value\ValueBuilderInterface;
use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\Exception\PhpVersionException;
use Donquixote\QuickAttributes\Exception\SyntaxException;
use Donquixote\QuickAttributes\Exception\UnsupportedSyntaxException;
use Donquixote\QuickAttributes\FileTokens\FileTokensInterface;
use Donquixote\QuickAttributes\Util\ParserAssertUtil;
use Donquixote\QuickAttributes\Util\ParserUtil;
use Donquixote\QuickAttributes\Util\ReservedWordUtil;
use Donquixote\QuickAttributes\Util\TokenPositionUtil;
use Donquixote\QuickAttributes\Util\VersionDependentTokens;

abstract class FileTokenParser implements FileTokenParserInterface {

  /**
   * @var string
   */
  protected string $terminatedNamespace = '';

  /**
   * @var array<string, string>
   */
  protected array $imports = [];

  /**
   * @var class-string|null
   */
  protected ?string $class = null;

  public static function create(): self {
    return \PHP_VERSION_ID < 80000
      ? new FileTokenParser_Php7()
      : new FileTokenParser_Php8();
  }

  /**
   * @param string|null $namespace
   *
   * @return static
   */
  private function withNamespace(?string $namespace): self {
    $clone = clone $this;
    $clone->terminatedNamespace = ($namespace !== null)
      ? $namespace . '\\'
      : '';
    return $clone;
  }

  /**
   * @param array<string, string> $imports
   *
   * @return static
   */
  private function withImports(array $imports): self {
    $clone = clone $this;
    $clone->imports = $imports;
    return $clone;
  }

  /**
   * @param class-string $class
   *
   * @return static
   */
  private function withClass(string $class): self {
    $clone = clone $this;
    $clone->class = $class;
    return $clone;
  }

  /**
   * @param \Donquixote\QuickAttributes\FileTokens\FileTokensInterface $fileTokens
   * @param \Donquixote\QuickAttributes\Builder\File\FileBuilderInterface $fileBuilder
   *
   * @return \Iterator<int, true>
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function parseFileTokens(FileTokensInterface $fileTokens, FileBuilderInterface $fileBuilder): \Iterator {

    $tokens = $fileTokens->getClassFileHead();

    if ($tokens === null) {
      $tokens = $fileTokens->getAll();
      return $this->parseFileScope($tokens, null, $fileBuilder);
    }

    return $this->parseFileScope($tokens, $fileTokens, $fileBuilder);
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param \Donquixote\QuickAttributes\FileTokens\FileTokensInterface|null $remainingFileTokens
   * @param \Donquixote\QuickAttributes\Builder\File\FileBuilderInterface $fileBuilder
   *
   * @return \Iterator<int, true>
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  private function parseFileScope(array $tokens, ?FileTokensInterface $remainingFileTokens, FileBuilderInterface $fileBuilder): \Iterator {

    $namespace = null;
    if ($tokens[0][0] !== \T_OPEN_TAG) {
      throw UnsupportedSyntaxException::fromTokenPos($tokens, 0, 'Only files starting with T_OPEN_TAG are supported.');
    }

    for ($pos = 1;; ++$pos) {
      $token = $tokens[$pos];
      if (\is_string($token)) {
        // Character tokens indicate that file head ends here.
        break;
      }

      switch ($token[0]) {
        case \T_WHITESPACE:
        case \T_DOC_COMMENT:
          // Ignore.
          break;

        case \T_COMMENT:
          if (\substr($token[1], 0, 2) === '#[') {
            // This is an attribute!
            // Continue with code below.
            break 2;
          }
          // Ignore.
          break;

        case VersionDependentTokens::T_ATTRIBUTE:
          // This is an attribute!
          // Continue with code below.
          break 2;

        case \T_DECLARE:
          ++$pos;
          ParserUtil::skipFillerWsExpectChar($tokens, $pos, '(');
          // Ignore the declare, for now.
          ParserUtil::skipSubtree($tokens, $pos);
          ++$pos;
          ParserUtil::skipFillerWsExpectChar($tokens, $pos, ';');
          break;

        case \T_NAMESPACE:
          $namespace = $this->parseNamespace($tokens, $pos);
          \assert(ParserAssertUtil::expect($tokens, $pos, ';'));
          ++$pos;
          break 2;

        default:
          // Ignore.
          break 2;
      }
    }

    return $this
      ->withNamespace($namespace)
      ->parseNamespaceContents(
        $tokens,
        $remainingFileTokens,
        $pos,
        $fileBuilder);
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param \Donquixote\QuickAttributes\FileTokens\FileTokensInterface|null $remainingFileTokens
   * @param int $pos
   * @param \Donquixote\QuickAttributes\Builder\File\FileBuilderInterface $fileBuilder
   *
   * @return \Iterator<int, true>
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  private function parseNamespaceContents(array $tokens, ?FileTokensInterface $remainingFileTokens, int $pos, FileBuilderInterface $fileBuilder): \Iterator {
    $imports = [];
    $attrPositions = [];
    for (;; ++$pos) {
      $token = $tokens[$pos];
      if (\is_string($token)) {
        switch ($token) {
          case '(':
          case '[':
            ParserUtil::skipSubtree($tokens, $pos);
            \assert(ParserAssertUtil::expectOneOf($tokens, $pos, [')', ']']));
            break;

          case '{':
            // Subtrees with '{' are special, they can contain a class, trait or
            // interface declaration.
            // This also means that the tokens could be cut off within that
            // subtree.
            $subtreeStartPos = $pos;
            try {
              ParserUtil::skipSubtree($tokens, $pos);
            }
            catch (SyntaxException $e) {
              // This could be unexpected end of file, if $tokens only contains
              // the head of a supposed class file.
              // This can be the case if the class is declared within an if ().
              // Load the complete token list, and try again.
              if ($remainingFileTokens === null) {
                throw $e;
              }
              $tokens = $remainingFileTokens->getAll();
              $remainingFileTokens = null;
              $pos = $subtreeStartPos;
              \assert(ParserAssertUtil::expect($tokens, $pos, '{'));
              ParserUtil::skipSubtree($tokens, $pos);
            }
            \assert(ParserAssertUtil::expect($tokens, $pos, '}'));
            break;

          case '"':
            ParserUtil::skipDoubleQuotedString($tokens, $pos);
            \assert(ParserAssertUtil::expect($tokens, $pos, '"'));
            break;

          case ')':
          case '}':
          case ']':
            throw SyntaxException::unexpected($tokens, $pos, 'in file scope');

          case '#':
            // End of file.
            return;

          default:
            break;
        }
      }
      else {
        switch ($token[0]) {
          case \T_WHITESPACE:
            // Ignore, but keep attributes.
            continue 2;

          case \T_DOC_COMMENT:
            // Ignore, but keep attributes.
            continue 2;

          case \T_COMMENT:
            if (\substr($token[1], 0, 2) === '#[') {
              // This is an attribute!
              $attrPositions[] = $pos;
            }
            // Keep attributes.
            continue 2;

          case VersionDependentTokens::T_ATTRIBUTE:
            // It is too early to parse the attribute, because we don't have the
            // builder object, and we don't know yet if it is part of a class.
            $attrPositions[] = $pos;
            $this->skipNativeAttribute($tokens, $pos);
            \assert(ParserAssertUtil::expect($tokens, $pos, ']'));
            continue 2;

          case \T_PRIVATE:
          case \T_PUBLIC:
          case \T_PROTECTED:
            throw SyntaxException::unexpected($tokens, $pos, 'outside of class scope');

          case \T_STATIC:
          case \T_FINAL:
          case \T_ABSTRACT:
            // Ignore modifiers, but keep attributes.
            continue 2;

          case \T_NAMESPACE:
            if ($this->terminatedNamespace !== '') {
              throw SyntaxException::fromTokenPos($tokens, $pos, 'Cannot redeclare namespace.');
            }
            throw SyntaxException::unexpected($tokens, $pos, 'after non-declare statements');

          case \T_USE:
            $this->parseImportGroup($tokens, $pos, $imports);
            break;

          case \T_FUNCTION:
            $shortname = $this->parseFunctionHead($tokens, $pos);
            \assert(ParserAssertUtil::expect($tokens, $pos, '('));
            if ($shortname === null) {
              // Anonymous function. Ignore.
              // Skip the parameter list first.
              ParserUtil::skipSubtree($tokens, $pos);
              ++$pos;
              $id = ParserUtil::skipFillerWs($tokens, $pos);
              if ($id === \T_USE) {
                ++$pos;
                ParserUtil::skipFillerWsExpectChar($tokens, $pos, '(');
                ParserUtil::skipSubtree($tokens, $pos);
                \assert(ParserAssertUtil::expect($tokens, $pos, ')'));
              }
              elseif ($id === '{') {
                --$pos;
              }
              // Ignore the rest.
              break;
            }
            /** @var callable-string $functionQcn */
            $functionQcn = $this->terminatedNamespace . $shortname;
            $functionParser = $this->withImports($imports);
            $functionBuilder = $fileBuilder->addFunction($functionQcn, $imports);
            $functionParser->parseAttrPositions(
              $functionBuilder->buildAttributes(),
              $tokens,
              $attrPositions);
            yield true;
            yield from $functionParser->parseParams(
              $tokens,
              $pos,
              $functionBuilder->buildParameters());
            \assert(ParserAssertUtil::expect($tokens, $pos, ')'));
            ++$pos;
            $id = ParserUtil::skipFillerWs($tokens, $pos);
            if ($id === ':') {
              $id = $this->skipType($tokens, $pos, 'in return type');
            }
            if ($id !== '{') {
              throw SyntaxException::expectedButFound($tokens, $pos, '{ or ;');
            }
            ParserUtil::skipSubtree($tokens, $pos);
            \assert(ParserAssertUtil::expect($tokens, $pos, '}'));
            break;

          case \T_CLASS:
          case \T_INTERFACE:
          case \T_TRAIT:
            ++$pos;
            $shortname = ParserUtil::skipFillerWsExpectTString($tokens, $pos);
            /** @var class-string $class */
            $class = $this->terminatedNamespace . $shortname;
            $classParser = $this->withImports($imports)->withClass($class);
            $classBuilder = $fileBuilder->addClass($class, $imports);
            $classParser->parseAttrPositions(
              $classBuilder->buildAttributes(),
              $tokens,
              $attrPositions);
            yield true;

            // Get the full version of the tokens now.
            if ($remainingFileTokens !== null) {
              $tokens = $remainingFileTokens->getAll();
              $remainingFileTokens = null;
            }

            $this->skipClassLikeExtendsImplements($tokens, $pos);
            \assert(ParserAssertUtil::expect($tokens, $pos, '{'));
            yield from $classParser->parseClassLikeBody(
              $tokens, 
              $pos,
              $classBuilder->buildClassBody());
            \assert(ParserAssertUtil::expect($tokens, $pos, '}'));
            yield true;
            break;

          case \T_STRING:
          case \T_NS_SEPARATOR:
          case VersionDependentTokens::T_NAME_QUALIFIED:
            // This could be the start of a method call. Ignore.
            break;

          default:
            // Ignore.
            break;
        }
      }
    }
  }  // @codeCoverageIgnore

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position of T_NAMESPACE.
   *   After: Position of ';'.
   *
   * @return string
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  abstract protected function parseNamespace(array $tokens, int &$pos): string;

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position of class name (T_STRING).
   *   After: Position at closing '}' of class body.
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  private function skipClassLikeExtendsImplements(array $tokens, int &$pos): void {
    \assert(ParserAssertUtil::expect($tokens, $pos, \T_STRING));

    // Skip all extends and implements.
    for (++$pos;; ++$pos) {
      $token = $tokens[$pos];
      if (\is_string($token)) {
        switch ($token) {
          case ',':
            break;

          case '{':
            return;

          default:
            throw SyntaxException::unexpected($tokens, $pos, 'before class body');
        }
      }
      else {
        switch ($token[0]) {
          case \T_EXTENDS:
          case \T_IMPLEMENTS:
          case \T_STRING:
          case \T_NS_SEPARATOR:
          case \T_COMMENT:
          case \T_DOC_COMMENT:
          case \T_WHITESPACE:
            break;

          default:
            throw SyntaxException::unexpected($tokens, $pos, 'before class body');
        }
      }
    }
  }  // @codeCoverageIgnore

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   * @param \Donquixote\QuickAttributes\Builder\ClassBody\ClassBodyBuilderInterface $classBodyBuilder
   *
   * @return \Iterator<int, true>
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  private function parseClassLikeBody(array $tokens, int &$pos, ClassBodyBuilderInterface $classBodyBuilder): \Iterator {
    \assert(ParserAssertUtil::expect($tokens, $pos, '{'));
    $attrPositions = [];
    for (++$pos;; ++$pos) {
      $token = $tokens[$pos];
      if (\is_string($token)) {
        switch ($token) {

          case '}':
            $classBodyBuilder->markAsComplete();
            return;

          case '?':
            // This is a nullable property type.
            $id = $this->skipType($tokens, $pos, 'in property type');
            if ($id !== \T_VARIABLE) {
              throw SyntaxException::expectedButFound($tokens, $pos, 'T_VARIABLE');
            }
            // Let the next cycle handle the property.
            --$pos;
            continue 2;

          default:
            throw SyntaxException::unexpected($tokens, $pos, 'in class body');
        }
      }
      else {
        switch ($token[0]) {
          case \T_WHITESPACE:
            // Ignore. Don't clear attributes.
            continue 2;

          case \T_DOC_COMMENT:
            // Ignore. Don't clear attributes.
            continue 2;

          case \T_COMMENT:
            if (\substr($token[1], 0, 2) === '#[') {
              // This is an attribute!
              $attrPositions[] = $pos;
            }
            // Don't clear attributes.
            continue 2;

          case VersionDependentTokens::T_ATTRIBUTE:
            $attrPositions[] = $pos;
            $this->skipNativeAttribute($tokens, $pos);
            \assert(ParserAssertUtil::expect($tokens, $pos, ']'));
            continue 2;

          case \T_PRIVATE:
          case \T_PUBLIC:
          case \T_PROTECTED:
          case \T_STATIC:
          case \T_FINAL:
          case \T_ABSTRACT:
            // Ignore modifiers. Don't clear attributes.
            continue 2;

          case \T_CLASS:
          case \T_INTERFACE:
          case \T_TRAIT:
          case \T_NAMESPACE:
            throw SyntaxException::unexpected($tokens, $pos, 'in class scope.');

          case \T_USE:
            // Ignore use traits. Do clear attributes.
            // Traits are already available via native reflection.
            $this->skipUseTraits($tokens, $pos);
            \assert(ParserAssertUtil::expectOneOf($tokens, $pos, [';', '}']));
            break;

          case \T_FUNCTION:
            $method = $this->parseFunctionHead($tokens, $pos, true);
            \assert($method !== null);
            $methodBuilder = $classBodyBuilder->addMethod($method);
            $this->parseAttrPositions(
              $methodBuilder->buildAttributes(),
              $tokens,
              $attrPositions);
            yield true;
            yield from $this->parseParams(
              $tokens,
              $pos,
              $methodBuilder->buildParameters());
            \assert(ParserAssertUtil::expect($tokens, $pos, ')'));
            ++$pos;
            $id = ParserUtil::skipFillerWs($tokens, $pos);
            if ($id === ':') {
              $id = $this->skipType($tokens, $pos, 'in return type');
            }
            if ($id === '{') {
              ParserUtil::skipSubtree($tokens, $pos);
            }
            elseif ($id !== ';') {
              throw SyntaxException::expectedButFound($tokens, $pos, '{ or ;');
            }
            break;

          case \T_STRING:
          case \T_NS_SEPARATOR:
          case VersionDependentTokens::T_NAME_FULLY_QUALIFIED:
          case VersionDependentTokens::T_NAME_QUALIFIED:
          case \T_ARRAY:
          case \T_CALLABLE:
            // This is a property type.
            $id = $this->skipType($tokens, $pos, 'in property type');
            if ($id !== \T_VARIABLE) {
              throw SyntaxException::expectedButFound($tokens, $pos, 'T_VARIABLE');
            }
            // Let the next cycle handle the property.
            --$pos;
            continue 2;

          case \T_VARIABLE:
            foreach ($this->parseClassPropertyGroup($tokens, $pos) as $name) {
              $propertyBuilder = $classBodyBuilder->addProperty($name);
              $this->parseAttrPositions(
                $propertyBuilder,
                $tokens,
                $attrPositions);
            }
            yield true;
            break;

          case \T_CONST:
            foreach ($this->parseClassConstGroup($tokens, $pos) as $name) {
              $constBuilder = $classBodyBuilder->addConstant($name);
              $this->parseAttrPositions(
                $constBuilder,
                $tokens,
                $attrPositions);
            }
            yield true;
            break;

          default:
            throw SyntaxException::unexpected($tokens, $pos, 'in class body');
        }
      }
      $attrPositions = [];
    }
  }  // @codeCoverageIgnore

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position of T_FUNCTION.
   *   After: Position of '('.
   * @param bool $isClassMember
   *   TRUE if this is a class member.
   *
   * @return string|null
   *   Function shortname, or NULL if anonymous.
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  private function parseFunctionHead(array $tokens, int &$pos, bool $isClassMember = false): ?string {
    \assert(ParserAssertUtil::expect($tokens, $pos, \T_FUNCTION));

    ++$pos;
    $id = ParserUtil::skipFillerWs($tokens, $pos);
    if ($id === '&' || $id === VersionDependentTokens::T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG) {
      ++$pos;
      $id = ParserUtil::skipFillerWs($tokens, $pos);
    }

    if ($id === '(') {
      // Anonymous function.
      if ($isClassMember) {
        throw SyntaxException::fromTokenPos($tokens, $pos, 'Anonymous function in class not allowed.');
      }
      return null;
    }

    if ($id !== \T_STRING) {
      if (!$isClassMember) {
        throw SyntaxException::expectedButFound($tokens, $pos, 'method name');
      }
      if (!\is_array($tokens[$pos])
        || !ReservedWordUtil::validMemberName($tokens[$pos][1])
      ) {
        throw SyntaxException::expectedButFound($tokens, $pos, 'method name');
      }
    }

    $shortName = $tokens[$pos][1];
    ++$pos;
    ParserUtil::skipFillerWsExpectChar($tokens, $pos, '(');
    return $shortName;
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position at '(' before the parameters.
   *   After: Position at ')' after the parameters.
   * @param \Donquixote\QuickAttributes\Builder\Parameters\ParametersBuilderInterface $parametersBuilder
   *
   * @return \Iterator<int, true>
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  private function parseParams(array $tokens, int &$pos, ParametersBuilderInterface $parametersBuilder): \Iterator {
    \assert(ParserAssertUtil::expect($tokens, $pos, '('));
    $attrPositions = [];
    for (++$pos;; ++$pos) {
      if (\is_string($tokens[$pos])) {
        switch ($tokens[$pos]) {

          case ')':
            break 2;

          case '#':
            throw SyntaxException::fromTokenPos($tokens, $pos, "Unexpected EOF in parameters.");

          case '?':
            // Found a nullable parameter type.
            $id = $this->skipType($tokens, $pos, 'in parameter type');
            if ($id === '&') {
              ++$pos;
              $id = ParserUtil::skipFillerWs($tokens, $pos);
            }
            if ($id === \T_ELLIPSIS) {
              ++$pos;
              $id = ParserUtil::skipFillerWs($tokens, $pos);
            }
            if ($id !== \T_VARIABLE) {
              throw SyntaxException::expectedButFound($tokens, $pos, 'T_VARIABLE');
            }
            break;

          case '&':
            // Start a by-reference parameter.
            ++$pos;
            $id = ParserUtil::skipFillerWs($tokens, $pos);
            if ($id === \T_ELLIPSIS) {
              ++$pos;
              $id = ParserUtil::skipFillerWs($tokens, $pos);
            }
            if ($id !== \T_VARIABLE) {
              throw SyntaxException::expectedButFound($tokens, $pos, 'T_VARIABLE');
            }
            break;

          default:
            throw SyntaxException::unexpected($tokens, $pos, 'in parameters');
        }
      }
      else {
        switch ($tokens[$pos][0]) {
          case \T_WHITESPACE:
          case \T_DOC_COMMENT:
            continue 2;

          case \T_COMMENT:
            if (\substr($tokens[$pos][1], 0, 2) === '#[') {
              // This is an attribute!
              $attrPositions[] = $pos;
            }
            continue 2;

          case VersionDependentTokens::T_ATTRIBUTE:
            $attrPositions[] = $pos;
            $this->skipNativeAttribute($tokens, $pos);
            \assert(ParserAssertUtil::expect($tokens, $pos, ']'));
            continue 2;

          case \T_PRIVATE:
          case \T_PROTECTED:
          case \T_PUBLIC:
            // This only works in PHP 8.
            // @todo Complain, if not available in PHP version.
            continue 2;

          case \T_STRING:
          case \T_NS_SEPARATOR:
          case VersionDependentTokens::T_NAME_FULLY_QUALIFIED:
          case VersionDependentTokens::T_NAME_QUALIFIED:
          case \T_ARRAY:
          case \T_CALLABLE:
            // Found a parameter type.
            $id = $this->skipType($tokens, $pos, 'in parameter type');
            if ($id === '&') {
              ++$pos;
              $id = ParserUtil::skipFillerWs($tokens, $pos);
            }
            if ($id === \T_ELLIPSIS) {
              ++$pos;
              $id = ParserUtil::skipFillerWs($tokens, $pos);
            }
            if ($id !== \T_VARIABLE) {
              throw SyntaxException::expectedButFound($tokens, $pos, 'T_VARIABLE');
            }
            break;

          case VersionDependentTokens::T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG:
            // Start a by-reference parameter.
            ++$pos;
            $id = ParserUtil::skipFillerWs($tokens, $pos);
            if ($id === \T_ELLIPSIS) {
              ++$pos;
              $id = ParserUtil::skipFillerWs($tokens, $pos);
            }
            if ($id !== \T_VARIABLE) {
              throw SyntaxException::expectedButFound($tokens, $pos, 'T_VARIABLE');
            }
            break;

          case \T_ELLIPSIS:
            // Found a parameter type.
            ++$pos;
            $id = ParserUtil::skipFillerWs($tokens, $pos);
            if ($id !== \T_VARIABLE) {
              throw SyntaxException::expectedButFound($tokens, $pos, 'T_VARIABLE');
            }
            break;

          case \T_VARIABLE:
            break;

          default:
            throw SyntaxException::unexpected($tokens, $pos, 'in parameters');
        }
        \assert(ParserAssertUtil::expect($tokens, $pos, \T_VARIABLE));
      }
      \assert(ParserAssertUtil::expect($tokens, $pos, \T_VARIABLE));
      $name = \substr($tokens[$pos][1], 1);
      $attributesBuilder = $parametersBuilder->addParameter($name);
      $this->parseAttrPositions(
        $attributesBuilder,
        $tokens,
        $attrPositions);
      yield true;
      $attrPositions = [];
      ++$pos;
      $id = ParserUtil::skipFillerWs($tokens, $pos);
      if ($id === '=') {
        $id = $this->skipVarDefault($tokens, $pos, true);
      }
      // Skip until the comma or ')'.
      if ($id === ')') {
        break;
      }
      if ($id === ',') {
        continue;
      }
      throw SyntaxException::unexpected($tokens, $pos, 'in parameters');
    }

    $parametersBuilder->markAsComplete();
    yield true;
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  private function skipNativeAttribute(array $tokens, int &$pos): void {
    $startpos = $pos;
    try {
      // Skip the attribute, in the fast way.
      ParserUtil::skipSubtree($tokens, $pos);
    }
    catch (ParserException $e) {
      $pos = $startpos;
      // Produce an exception with a more meaningful message.
      ($this->class !== null ? $this : $this->withClass(\stdClass::class))
        ->parseAttributes(new AttributesBuilder_NoOp(), $tokens, $pos);
    }
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *
   * @return string[]
   *   Property names without the '$'.
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  private function parseClassPropertyGroup(array $tokens, int &$pos): array {
    \assert(ParserAssertUtil::expect($tokens, $pos, \T_VARIABLE));
    $names = [\substr($tokens[$pos][1], 1)];

    ++$pos;
    $id = ParserUtil::skipFillerWs($tokens, $pos);
    while (true) {
      if ($id === '=') {
        $id = $this->skipVarDefault($tokens, $pos, false);
      }
      if ($id === ';') {
        break;
      }
      // If it is not a ';', it must be a ',', according to the documented
      // behavior of ->skipVarDefault().
      \assert(ParserAssertUtil::expect($tokens, $pos, ','));
      ++$pos;
      $id = ParserUtil::skipFillerWs($tokens, $pos);
      if ($id !== \T_VARIABLE) {
        throw SyntaxException::expectedButFound($tokens, $pos, 'T_VARIABLE');
      }
      $names[] = \substr($tokens[$pos][1], 1);
      ++$pos;
      $id = ParserUtil::skipFillerWs($tokens, $pos);
    }

    return $names;
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *
   * @return string[]
   *   Constant names.
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  private function parseClassConstGroup(array $tokens, int &$pos): array {
    \assert(ParserAssertUtil::expect($tokens, $pos, \T_CONST));

    ++$pos;
    $names = [];
    $names[] = ParserUtil::skipFillerWsExpectMemberName($tokens, $pos);

    ++$pos;
    $id = ParserUtil::skipFillerWs($tokens, $pos);
    while (true) {
      if ($id === '=') {
        $id = $this->skipVarDefault($tokens, $pos, false);
      }
      if ($id === ';') {
        return $names;
      }
      // If it is not a ';', it must be a ',', according to the documented
      // behavior of ->skipVarDefault().
      \assert(ParserAssertUtil::expect($tokens, $pos, ','));
      ++$pos;
      $id = ParserUtil::skipFillerWs($tokens, $pos);
      if ($id !== \T_STRING) {
        throw SyntaxException::expectedButFound($tokens, $pos, 'T_STRING');
      }
      $names[] = $tokens[$pos][1];
      ++$pos;
      $id = ParserUtil::skipFillerWs($tokens, $pos);
    }
  }  // @codeCoverageIgnore

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   * @param bool $isParam
   *
   * @return string
   *   Symbol that follows the default value.
   *   One of ')' or ',' if $isParam is TRUE.
   *   One of ';' or ',' if $isParam is FALSE.
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  private function skipVarDefault(array $tokens, int &$pos, bool $isParam): string {
    \assert(ParserAssertUtil::expect($tokens, $pos, '='));
    for (++$pos;; ++$pos) {
      $token = $tokens[$pos];
      if (!\is_string($token)) {
        // Ignore any non-char tokens.+
        continue;
      }

      switch ($token) {
        case '(':
        case '{':
        case '[':
          ParserUtil::skipSubtree($tokens, $pos);
          \assert(ParserAssertUtil::expectOneOf($tokens, $pos, [')', '}', ']']));
          break;

        case ',':
          return $token;

        case ')':
          if (!$isParam) {
            throw SyntaxException::unexpected($tokens, $pos, 'after property or const default value');
          }
          return $token;

        case ';':
          if ($isParam) {
            throw SyntaxException::unexpected($tokens, $pos, 'after parameter default value');
          }
          return $token;

        case '}':
        case ']':
          throw SyntaxException::fromTokenPos($tokens, $pos, "Unexpected '$token' in parameters.");

        case '#':
          throw SyntaxException::fromTokenPos($tokens, $pos, "Unexpected EOF in parameters.");

        default:
          break;
      }
    }
    // Silence Psalm.
    /** @noinspection PhpUnreachableStatementInspection */
    throw new \RuntimeException('Unreachable code.');  // @codeCoverageIgnore
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position of 'use' statement.
   *   After: Directly on ';'.
   * @param array<string, string> $imports
   *   Format: $[$alias] = $qcn.
   *
   * @return void
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  abstract protected function parseImportGroup(array $tokens, int &$pos, array &$imports): void;

  /**
   * Skips the type before a property or parameter, or after a function head.
   *
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Before the first non-verified token of type declaration.
   *   After: Position of '{' or ';' or T_VARIABLE or &.
   *
   * @return string|int
   *   One of '{' or ';'.
   *
   * @psalm-suppress InvalidNullableReturnType
   *   Psalm gets confused with non-breaking loops.
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  private function skipType(array $tokens, int &$pos, string $where) {
    for (++$pos;; ++$pos) {
      $token = $tokens[$pos];
      if (\is_string($token)) {
        switch ($token) {
          case '{':
          case ';':
            // Seems like a function type.
            return $token;

          case '&':
            // Seems like a type for a by-reference parameter.
            return $token;

          case '|':
            // @todo Make this configurable, or dependent on php version.
            throw PhpVersionException::fromTokenPos($tokens, $pos, 'Union types are only available in PHP 8.');

          case '?':
            break;

          default:
            throw SyntaxException::unexpected($tokens, $pos, $where);
        }
      }
      else {
        switch ($token[0]) {
          case \T_STRING:
          case \T_NS_SEPARATOR:
          case VersionDependentTokens::T_NAME_FULLY_QUALIFIED:
          case VersionDependentTokens::T_NAME_QUALIFIED:
          case \T_WHITESPACE:
          case \T_COMMENT:
          case \T_DOC_COMMENT:
          case \T_ARRAY:
          case \T_CALLABLE:
            break;

          case \T_VARIABLE:
            // Seems like a property or parameter type.
            return \T_VARIABLE;

          case VersionDependentTokens::T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG:
            // Allow calling code ot handle this like in PHP 7.
            return '&';

          case \T_ELLIPSIS:
            // Seems like a variadic parameter.
            return \T_ELLIPSIS;

          default:
            throw SyntaxException::unexpected($tokens, $pos, $where);
        }
      }
    }
  }  // @codeCoverageIgnore

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position of T_USE.
   *   After: Position of ';' or '}'.
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  private function skipUseTraits(array $tokens, int &$pos): void {
    \assert(ParserAssertUtil::expect($tokens, $pos, \T_USE));
    for (++$pos;; ++$pos) {
      $token = $tokens[$pos];
      if (\is_string($token)) {
        switch ($token) {
          case '{':
            ParserUtil::skipSubtree($tokens, $pos);
            \assert(ParserAssertUtil::expect($tokens, $pos, '}'));
            return;

          case ';':
            return;

          case ',':
            break;

          default:
            throw SyntaxException::unexpected($tokens, $pos, 'in use trait statement');
        }
      }
      else {
        switch ($token[0]) {
          case \T_NS_SEPARATOR:
          case \T_STRING:
          case \T_WHITESPACE:
          case \T_COMMENT:
          case \T_DOC_COMMENT:
          case \T_AS:
            break;

          default:
            throw SyntaxException::unexpected($tokens, $pos, 'in use trait statement');
        }
      }
    }
  }  // @codeCoverageIgnore

  /**
   * @param \Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilderInterface $builder
   * @param list<string|array{int, string, int}> $tokens
   * @param list<int> $positions
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  private function parseAttrPositions(AttributesBuilderInterface $builder, array $tokens, array $positions): void {
    foreach ($positions as $pos) {
      if ($tokens[$pos][0] === \T_COMMENT) {
        $this->parseAttrComment($builder, $tokens, $pos);
      }
      elseif ($tokens[$pos][0] === VersionDependentTokens::T_ATTRIBUTE) {
        $this->parseAttributes($builder, $tokens, $pos);
      }
    }
  }

  /**
   * @param \Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilderInterface $builder
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  private function parseAttrComment(AttributesBuilderInterface $builder, array $tokens, int $pos): void {
    $comment = $tokens[$pos][1];
    [$line, $chrpos] = TokenPositionUtil::findLineChrPos($tokens, $pos);
    $tokens = $this->tokenizeAttrComment($comment, $line, $chrpos);
    \assert(\end($tokens) === '#');
    \assert(ParserAssertUtil::expect($tokens, 0, VersionDependentTokens::T_ATTRIBUTE));
    $i = 0;
    while (true) {
      \assert(ParserAssertUtil::expect($tokens, $i, VersionDependentTokens::T_ATTRIBUTE));
      $this->parseAttributes($builder, $tokens, $i);
      \assert(ParserAssertUtil::expect($tokens, $i, ']'));
      ++$i;
      $id = ParserUtil::skipFillerWs($tokens, $i);
      if ($id === '#') {
        // EOF reached.
        return;
      }
      if ($id === VersionDependentTokens::T_ATTRIBUTE) {
        continue;
      }
      throw UnsupportedSyntaxException::fromTokenPos($tokens, $i, 'Cannot have regular code after an attribute in the same line.');
    }
  }  // @codeCoverageIgnore

  /**
   * @param string $comment
   *   Single-line comment starting with '#[', ending with newline.
   * @param int $line
   * @param int $chrpos
   *
   * @return list<string|array{int, string, int}>
   *   Tokens starting with T_ATTRIBUTE, terminated with `#`.
   */
  private function tokenizeAttrComment(string $comment, int $line, int $chrpos): array {
    \assert(\substr($comment, 0, 2) === '#[');
    \assert(\strpos($comment, "\n") === \strlen($comment) - 1);
    \assert($line > 0 || $chrpos > 6);
    $php = '<?php '
      . \str_repeat("\n", $line - 1)
      . \str_repeat(' ', ($line > 1) ? ($chrpos + 1) : ($chrpos - 5))
      . \substr($comment, 2);
    /** @var list<string|array{int, string, int}> $tokens */
    $tokens = \token_get_all($php);
    $tokens[0] = [VersionDependentTokens::T_ATTRIBUTE, '#[', 0];
    /**
     * Tell psalm that $tokens is still a list, after setting $tokens[0].
     *
     * @var list<string|array{int, string, int}> $tokens
     */
    while (true) {
      $tkLast = \end($tokens);
      if ($tkLast[0] !== \T_COMMENT) {
        break;
      }
      // At this point it is known that $tkLast is an array, not a string.
      /** @var array{int, string, int} $tkLast */
      $comment = $tkLast[1];
      if ($comment[1] !== '[') {
        // That's a regular comment, not an attribute comment.
        break;
      }
      $php = '<?php ' . \substr($comment, 2);
      /** @var list<string|array{int, string, int}> $moreTokens */
      $moreTokens = \token_get_all($php);
      $moreTokens[0] = [VersionDependentTokens::T_ATTRIBUTE, '#[', 0];
      \array_pop($tokens);
      $tokens = [
        ...$tokens,
        ...$moreTokens,
      ];
    }

    $tokens[] = '#';
    return $tokens;
  }

  /**
   * @param \Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilderInterface $builder
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position of '#[' / T_ATTRIBUTE.
   *   After: Position of ']'.
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  private function parseAttributes(AttributesBuilderInterface $builder, array $tokens, int &$pos): void {
    \assert(ParserAssertUtil::expect($tokens, $pos, VersionDependentTokens::T_ATTRIBUTE));
    while (true) {
      \assert(ParserAssertUtil::expectOneOf($tokens, $pos, [VersionDependentTokens::T_ATTRIBUTE, ',']));
      ++$pos;
      ParserUtil::skipFillerWs($tokens, $pos);
      $iBkp0 = $pos;
      $qcn = $this->parseAttributeName($tokens, $pos);
      \assert($pos > $iBkp0);
      $args = $builder->addAttribute($qcn);
      $id = ParserUtil::skipFillerWs($tokens, $pos);
      if ($id === '(') {
        $this->parseArgs($args, $tokens, $pos);
        \assert(ParserAssertUtil::expect($tokens, $pos, ')'));
        ++$pos;
        $id = ParserUtil::skipFillerWs($tokens, $pos);
      }
      if ($id === ']') {
        return;
      }
      if ($id !== ',') {
        throw SyntaxException::expectedButFound($tokens, $pos, "']' or ','");
      }
    }
  }  // @codeCoverageIgnore

  /**
   * @param \Donquixote\QuickAttributes\Builder\Arguments\ArgumentsBuilderInterface $builder
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position of '('.
   *   After: Position of ')'.
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  private function parseArgs(ArgumentsBuilderInterface $builder, array $tokens, int &$pos): void {
    \assert(ParserAssertUtil::expect($tokens, $pos, '('));
    $namedPart = false;
    while (true) {
      \assert(ParserAssertUtil::expectOneOf($tokens, $pos, ['(', ',']));
      ++$pos;
      $id = ParserUtil::skipFillerWs($tokens, $pos);
      $key = null;
      if ($id === ')') {
        // Empty arg list, or trailing comma after last arg.
        break;
      }
      if ($id === \T_STRING) {
        // This could be a named arg, OR a part of a value expression.
        $iNamed = $pos + 1;
        $idNamed = ParserUtil::skipFillerWs($tokens, $iNamed);
        if ($idNamed === ':') {
          // This is indeed a named argument.
          $key = $tokens[$pos][1];
          $pos = $iNamed + 1;
          ParserUtil::skipFillerWs($tokens, $pos);
          $namedPart = true;
        }
      }
      if ($key === null && $namedPart) {
        throw SyntaxException::fromTokenPos($tokens, $pos, 'Cannot use positional argument after named argument');
      }
      $id = $this->parseValueExpression(
        $builder->addArgument($key),
        $tokens,
        $pos);
      if ($id === ')') {
        break;
      }
      if ($id === ',') {
        continue;
      }
      throw SyntaxException::expectedButFound($tokens, $pos, ', or )');
    }

    \assert($tokens[$pos] === ')');
  }

  /**
   * Attribute name, as QCN or FQCN.
   *
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before (good): Position of first T_STRING, T_NAME_QUALIFIED or
   *     T_NAME_FULLY_QUALIFIED.
   *   Before (bad): Anything else leads to a SyntaxException.
   *   After: Position after last T_STRING.
   *
   * @return class-string
   *   Resolved QCN.
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  abstract protected function parseAttributeName(array $tokens, int &$pos): string;

  /**
   * @param \Donquixote\QuickAttributes\Builder\Value\ValueBuilderInterface $result
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: First token of value expression.
   *   After: Position of ')' or ',' or '}' or ']'.
   *
   * @return string|int
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  private function parseValueExpression(ValueBuilderInterface $result, array $tokens, int &$pos) {
    $unary = '';
    $operand = $result;
    while (true) {
      $token = $tokens[$pos];
      if (\is_string($token)) {
        switch ($token) {
          case '-':
          case '!':
            $unary .= $token;
            ++$pos;
            ParserUtil::skipFillerWs($tokens, $pos);
            continue 2;

          case '(':
            ++$pos;
            ParserUtil::skipFillerWs($tokens, $pos);
            $id = $this->parseValueExpression($operand, $tokens, $pos);
            if ($id !== ')') {
              throw SyntaxException::expectedButFound($tokens, $pos, ')');
            }
            ++$pos;
            $id = ParserUtil::skipFillerWs($tokens, $pos);
            break;

          case '[':
            $this->parseArray($operand->startArray(), $tokens, $pos, ']');
            \assert($tokens[$pos] === ']');
            ++$pos;
            $id = ParserUtil::skipFillerWs($tokens, $pos);
            break;

          default:
            throw SyntaxException::unexpected($tokens, $pos, 'in const value expression');
        }
      }
      else {
        switch ($token[0]) {
          case \T_ARRAY:
            ++$pos;
            ParserUtil::skipFillerWsExpectChar($tokens, $pos, '(');
            $this->parseArray($operand->startArray(), $tokens, $pos, ')');
            \assert($tokens[$pos] === ')');
            ++$pos;
            $id = ParserUtil::skipFillerWs($tokens, $pos);
            break;

          case \T_LNUMBER:
          case \T_CONSTANT_ENCAPSED_STRING:
            $operand->setFixedValue(eval('return ' . $token[1] . ';'));
            ++$pos;
            /** @var string|int $id */
            $id = ParserUtil::skipFillerWs($tokens, $pos);
            break;

          case VersionDependentTokens::T_NAME_FULLY_QUALIFIED:
          case VersionDependentTokens::T_NAME_QUALIFIED:
          case VersionDependentTokens::T_STRING_8:
            $id = $this->parseConstRef($operand, $tokens, $pos);
            break;

          case VersionDependentTokens::T_STRING_7:
          case VersionDependentTokens::T_NS_SEPARATOR_7:
            $id = $this->parseConstRef($operand, $tokens, $pos);
            break;

          default:
            throw SyntaxException::expectedButFound($tokens, $pos, 'const value expression');
        }
      }
      if ($unary !== '') {
        $operand->applyUnaryOperator($unary);
      }
      \assert($id === $tokens[$pos][0]);
      if (\is_string($id)) {
        switch ($id) {
          case '-':
          case '+':
          case '*':
          case '/':
          case '.':
          case '&':
          case '|':
            // Binary operator.
            $operand = $result->appendBinaryOperator($id);
            ++$pos;
            ParserUtil::skipFillerWs($tokens, $pos);
            continue 2;

          case '[':
            // Array offset.
            // @todo Implement array offset.
            throw new \RuntimeException('Array offset not implemented.');

          case ')':
          case ']':
          case ',':
            // End of value expression.
            return $id;

          default:
            throw SyntaxException::unexpected($tokens, $pos, 'in const value expression');
        }
      }
      else {
        switch ($id) {

          case \T_DOUBLE_ARROW:
            // End of value expression.
            return $id;

          default:
            throw SyntaxException::unexpected($tokens, $pos, 'after value expression');
        }
      }
    }
  }

  /**
   * @param \Donquixote\QuickAttributes\Builder\Value\ArrayBuilderInterface $builder
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position of '(' or '['.
   *   After: Position of ')' or ']'.
   * @param string $endchar
   *   Expected end character. One of ')' or ']'.
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  private function parseArray(ArrayBuilderInterface $builder, array $tokens, int &$pos, string $endchar): void {
    \assert(ParserAssertUtil::expectOneOf($tokens, $pos, ['(', '[']));
    while (true) {
      \assert(ParserAssertUtil::expectOneOf($tokens, $pos, ['(', '[', ',']));
      ++$pos;
      $id = ParserUtil::skipFillerWs($tokens, $pos);
      if ($id === $endchar) {
        return;
      }
      $id = $this->parseValueExpression($builder->add(), $tokens, $pos);
      if ($id === \T_DOUBLE_ARROW) {
        ++$pos;
        ParserUtil::skipFillerWs($tokens, $pos);
        $id = $this->parseValueExpression($builder->mapTo(), $tokens, $pos);
      }
      if ($id === $endchar) {
        return;
      }
      if ($id !== ',') {
        throw SyntaxException::expectedButFound($tokens, $pos, "$endchar or ,");
      }
    }
  }

  /**
   * Expression referencing a global or class constant or *::class.
   *
   * @param \Donquixote\QuickAttributes\Builder\Value\ValueBuilderInterface $builder
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position of first T_STRING or T_NS_SEPARATOR.
   *   After: Non-whitespace position after last T_STRING or T_CLASS.
   *
   * @return string|int
   *   Token id following the constant expression.
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  abstract protected function parseConstRef(ValueBuilderInterface $builder, array $tokens, int &$pos);

}
