<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Parser;

use Donquixote\QuickAttributes\AttributeCommentParser\AttributeCommentMultiParser;
use Donquixote\QuickAttributes\AttributeCommentParser\AttributeCommentParser;
use Donquixote\QuickAttributes\AttributeCommentParser\AttributeCommentParserInterface;
use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\Exception\PhpVersionException;
use Donquixote\QuickAttributes\Exception\SyntaxException;
use Donquixote\QuickAttributes\Exception\UnsupportedSyntaxException;
use Donquixote\QuickAttributes\FileTokens\FileTokens_Common;
use Donquixote\QuickAttributes\FileTokens\FileTokensInterface;
use Donquixote\QuickAttributes\SymbolVisitor\ClassLike\ClassMemberVisitorInterface;
use Donquixote\QuickAttributes\SymbolVisitor\FunctionLike\ParamVisitorInterface;
use Donquixote\QuickAttributes\SymbolVisitor\SymbolVisitorInterface;
use Donquixote\QuickAttributes\Util\ParserAssertUtil;
use Donquixote\QuickAttributes\Util\ParserUtil;
use Donquixote\QuickAttributes\Util\ReservedWordUtil;
use Donquixote\QuickAttributes\Util\VersionDependentTokens;

abstract class FileParser implements FileTokenParserInterface {

  /**
   * @var \Donquixote\QuickAttributes\AttributeCommentParser\AttributeCommentMultiParser
   */
  private AttributeCommentMultiParser $attrCommentMultiParser;

  public static function create(): self {
    return \PHP_VERSION_ID < 80000
      ? new FileParserPhp7(new AttributeCommentParser())
      : new FileParserPhp8(new AttributeCommentParser());
  }

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\AttributeCommentParser\AttributeCommentParserInterface $attrCommentParser
   */
  public function __construct(AttributeCommentParserInterface $attrCommentParser) {
    $this->attrCommentMultiParser = new AttributeCommentMultiParser($attrCommentParser);
  }

  /**
   * @param string $file
   * @param \Donquixote\QuickAttributes\SymbolVisitor\SymbolVisitorInterface $visitor
   *
   * @return \Iterator<int, true>
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function parseFile(string $file, SymbolVisitorInterface $visitor): \Iterator {
    try {
      $fileTokens = FileTokens_Common::fromFile($file);
      yield from $this->parseFileTokens($fileTokens, $visitor);
    }
    catch (ParserException $e) {  // @codeCoverageIgnore
      $e->setSourceFile($file);  // @codeCoverageIgnore
      throw $e;  // @codeCoverageIgnore
    }
  }

  /**
   * @param \Donquixote\QuickAttributes\FileTokens\FileTokensInterface $fileTokens
   * @param \Donquixote\QuickAttributes\SymbolVisitor\SymbolVisitorInterface $visitor
   *
   * @return \Iterator<int, true>
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function parseFileTokens(FileTokensInterface $fileTokens, SymbolVisitorInterface $visitor): \Iterator {

    $headFirst = true;
    $tokens = $fileTokens->getClassFileHead();

    if ($tokens === null) {
      $headFirst = false;
      $tokens = $fileTokens->getAll();
    }

    $namespace = null;
    $terminatedNamespace = '';
    $imports = [];
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
          $terminatedNamespace = $namespace . '\\';
          \assert(ParserAssertUtil::expect($tokens, $pos, ';'));
          ++$pos;
          break 2;

        default:
          // Ignore.
          break 2;
      }
    }

    $attrComments = [];
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
              if (!$headFirst) {
                throw $e;
              }
              $tokens = $fileTokens->getAll();
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
              $attrComments[] = $token[1];
            }
            // Keep attributes.
            continue 2;

          case VersionDependentTokens::T_ATTRIBUTE:
            $attrComments[] = $this->parseNativeAttribute($tokens, $pos);
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
            if ($namespace !== null) {
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
            $functionQcn = $terminatedNamespace . $shortname;
            $attrCommentMultiParser = $this->attrCommentMultiParser->withContext(
              $namespace,
              $imports,
              null);
            $attributes = $attrCommentMultiParser->parseMultiple($attrComments);
            $paramVisitor = $visitor->addFunction($functionQcn, $imports, $attributes);
            yield true;
            yield from $this->parseParams(
              $tokens,
              $pos,
              $paramVisitor,
              $attrCommentMultiParser);
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
            $class = $terminatedNamespace . $shortname;
            $attrCommentMultiParser = $this->attrCommentMultiParser->withContext(
              $namespace,
              $imports,
              $class);
            $attributes = $attrCommentMultiParser->parseMultiple($attrComments);
            $memberVisitor = $visitor->addClass($class, $imports, $attributes);
            yield true;

            // Get the full version of the tokens now.
            if ($headFirst) {
              $tokens = $fileTokens->getAll();
            }

            $this->skipClassLikeExtendsImplements($tokens, $pos);
            \assert(ParserAssertUtil::expect($tokens, $pos, '{'));
            yield from $this->parseClassLikeBody($tokens, $pos, $memberVisitor, $attrCommentMultiParser);
            \assert(ParserAssertUtil::expect($tokens, $pos, '}'));
            $memberVisitor->markAsComplete();
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
   * @param \Donquixote\QuickAttributes\SymbolVisitor\ClassLike\ClassMemberVisitorInterface $memberVisitor
   * @param \Donquixote\QuickAttributes\AttributeCommentParser\AttributeCommentMultiParser $attrCommentMultiParser
   *   Attribute comment multi parser, filled with current context.
   *
   * @return \Iterator<int, true>
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  private function parseClassLikeBody(array $tokens, int &$pos, ClassMemberVisitorInterface $memberVisitor, AttributeCommentMultiParser $attrCommentMultiParser): \Iterator {
    \assert(ParserAssertUtil::expect($tokens, $pos, '{'));
    $attributeComments = [];
    for (++$pos;; ++$pos) {
      $token = $tokens[$pos];
      if (\is_string($token)) {
        switch ($token) {

          case '}':
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
              $attributeComments[] = $token[1];
            }
            // Don't clear attributes.
            continue 2;

          case VersionDependentTokens::T_ATTRIBUTE:
            $attributeComments[] = $this->parseNativeAttribute($tokens, $pos);
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
            $paramVisitor = $memberVisitor->addMethod(
              $method,
              $attrCommentMultiParser->parseMultiple($attributeComments));
            yield true;
            yield from $this->parseParams(
              $tokens,
              $pos,
              $paramVisitor,
              $attrCommentMultiParser);
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
            $attributes = $attrCommentMultiParser->parseMultiple($attributeComments);
            foreach ($this->parseClassPropertyGroup($tokens, $pos) as $name) {
              $memberVisitor->addProperty($name, $attributes);
            }
            yield true;
            break;

          case \T_CONST:
            $attributes = $attrCommentMultiParser->parseMultiple($attributeComments);
            foreach ($this->parseClassConstGroup($tokens, $pos) as $name) {
              $memberVisitor->addConstant($name, $attributes);
            }
            yield true;
            break;

          default:
            throw SyntaxException::unexpected($tokens, $pos, 'in class body');
        }
      }
      $attributeComments = [];
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
   * @param \Donquixote\QuickAttributes\SymbolVisitor\FunctionLike\ParamVisitorInterface $paramVisitor
   * @param \Donquixote\QuickAttributes\AttributeCommentParser\AttributeCommentMultiParser $attrCommentMultiParser
   *
   * @return \Iterator<int, true>
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  private function parseParams(array $tokens, int &$pos, ParamVisitorInterface $paramVisitor, AttributeCommentMultiParser $attrCommentMultiParser): \Iterator {
    \assert(ParserAssertUtil::expect($tokens, $pos, '('));
    $attributeComments = [];
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
              $attributeComments[] = $tokens[$pos][1];
            }
            continue 2;

          case VersionDependentTokens::T_ATTRIBUTE:
            $attributeComments[] = $this->parseNativeAttribute($tokens, $pos);
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
      $paramVisitor->addParameter(
        $name,
        $attrCommentMultiParser->parseMultiple($attributeComments));
      yield true;
      $attributeComments = [];
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

    $paramVisitor->markAsComplete();
    yield true;
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position of T_ATTRIBUTE = '#['.
   *   After: Position of closing ']'.
   *
   * @return string
   *   Snippet starting with '#[', ending with ']\n'.
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  private function parseNativeAttribute(array $tokens, int &$pos): string {
    $begin = $pos;
    ParserUtil::skipSubtree($tokens, $pos);
    \assert(ParserAssertUtil::expect($tokens, $pos, ']'));
    $snippet = ParserUtil::concatTokens($tokens, $begin, $pos + 1);
    \assert(\preg_match('@^#\[.*\]$@', $snippet));
    return $snippet . "\n";
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

}
