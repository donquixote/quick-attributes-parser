<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Parser;

use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\Exception\PhpVersionException;
use Donquixote\QuickAttributes\Exception\SyntaxException;
use Donquixote\QuickAttributes\Exception\UnsupportedSyntaxException;
use Donquixote\QuickAttributes\FileTokens\FileTokens_Common;
use Donquixote\QuickAttributes\FileTokens\FileTokensInterface;
use Donquixote\QuickAttributes\Util\ParserUtil;
use Donquixote\QuickAttributes\Util\ReservedWordUtil;
use Donquixote\QuickAttributes\Value\RawSymbolInfo;
use Donquixote\QuickAttributes\Value\SymbolHandle;

class FileParser {

  public function __construct() {
    if (PHP_VERSION_ID >= 80000) {
      throw new \RuntimeException('This class should only be used in PHP < 8.');
    }
  }

  /**
   * @param string $file
   *
   * @return iterable<SymbolHandle, RawSymbolInfo>
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function parseFile(string $file): iterable {
    try {
      $fileTokens = FileTokens_Common::fromFile($file);
      yield from $this->parseFileTokens($fileTokens);
    }
    catch (ParserException $e) {
      $e->setSourceFile($file);
      throw $e;
    }
  }

  /**
   * @param \Donquixote\QuickAttributes\FileTokens\FileTokensInterface $fileTokens
   *
   * @return iterable<SymbolHandle, RawSymbolInfo>
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function parseFileTokens(FileTokensInterface $fileTokens): iterable {

    $tokenss = $fileTokens->getTokenss();
    unset($fileTokens);

    $tokens = $tokenss->current();

    $pos = 0;
    $namespace = NULL;
    $terminatedNamespace = '';
    $imports = [];
    $attrComments = [];
    for ($i = $pos;; ++$i) {
      $token = $tokens[$i];
      if (is_string($token)) {
        switch ($token) {
          case '(':
          case '[':
            ParserUtil::skipSubtree($tokens, $i);
            assert(ParserUtil::expectOneOf($tokens, $i, [')', ']']));
            break;

          case '{':
            // Subtrees with '{' are special, they can contain a class, trait or
            // interface declaration.
            // This also means that the tokens could be cut off within that
            // subtree.
            try {
              ParserUtil::skipSubtree($tokens, $i);
            }
            catch (SyntaxException $e) {
              // This could be unexpected end of file, if $tokens only contains
              // the head of a supposed class file.
              // This can be the case if the class is declared within an if ().
              // Load the complete token list, and try again.
              $tokenss->next();
              if ($tokenss->valid()) {
                $tokens = $tokenss->current();
              }
              \assert(ParserUtil::expect($tokens, $i, '{'));
              ParserUtil::skipSubtree($tokens, $i);
            }
            assert(ParserUtil::expect($tokens, $i, '}'));
            break;

          case '"':
            ParserUtil::skipDoubleQuotedString($tokens, $i);
            assert(ParserUtil::expect($tokens, $i, '"'));
            break;

          case ')':
          case '}':
          case ']':
            throw SyntaxException::unexpected($tokens, $i, 'in file scope');

          case '#':
            // End of file.
            return;

          default:
            break;
        }
      }
      else {
        switch ($token[0]) {
          case T_WHITESPACE:
            // Ignore, but keep attributes.
            continue 2;

          case T_DOC_COMMENT:
            // Ignore, but keep attributes.
            continue 2;

          case T_COMMENT:
            if (substr($token[1], 0, 2) === '#[') {
              // This is an attribute!
              $attrComments[] = $token[1];
            }
            // Keep attributes.
            continue 2;

          case T_PRIVATE:
          case T_PUBLIC:
          case T_PROTECTED:
          case T_STATIC:
          case T_FINAL:
          case T_ABSTRACT:
            // Ignore modifiers, but keep attributes.
            continue 2;

          case T_NAMESPACE:
            if ($namespace !== NULL) {
              throw new SyntaxException('Cannot redeclare namespace.');
            }
            $namespace = $this->parseNamespace($tokens, $i);
            $terminatedNamespace = $namespace . '\\';
            break;

          case T_USE:
            $this->parseImportGroup($tokens, $i, $imports);
            break;

          case T_FUNCTION:
            $shortname = $this->parseFunctionHead($tokens, $i);
            \assert(ParserUtil::expect($tokens, $i, '('));
            if ($shortname === NULL) {
              // Anonymous function. Ignore.
              // Skip the parameter list first.
              ParserUtil::skipSubtree($tokens, $i);
              ++$i;
              $id = ParserUtil::skipFillerWs($tokens, $i);
              if ($id === T_USE) {
                ++$i;
                ParserUtil::skipFillerWsExpectChar($tokens, $i, '(');
                ParserUtil::skipSubtree($tokens, $i);
                \assert(ParserUtil::expect($tokens, $i, ')'));
              }
              elseif ($id === '{') {
                --$i;
              }
              // Ignore the rest.
              break;
            }
            $functionQcn = $terminatedNamespace . $shortname;
            $symbol = SymbolHandle::fromFunction($functionQcn);
            yield $symbol => RawSymbolInfo::forTopLevelSymbol($attrComments, $imports);
            foreach ($this->parseParams($tokens, $i) as $paramDollarName => $paramAttrComments) {
              $symbol = SymbolHandle::fromFunctionParameter(
                $functionQcn,
                substr($paramDollarName, 1));
              yield $symbol => RawSymbolInfo::forInnerSymbol($paramAttrComments);
            }
            \assert(ParserUtil::expect($tokens, $i, ')'));
            ++$i;
            $id = ParserUtil::skipFillerWs($tokens, $i);
            if ($id === ':') {
              $id = $this->skipReturnType($tokens, $i);
              \assert(ParserUtil::expectOneOf($tokens, $i, ['{', ';']));
            }
            if ($id !== '{') {
              throw SyntaxException::expectedButFound($tokens, $i, '{ or ;');
            }
            ParserUtil::skipSubtree($tokens, $i);
            \assert(ParserUtil::expect($tokens, $i, '}'));
            break;

          case T_CLASS:
          case T_INTERFACE:
          case T_TRAIT:
            ++$i;
            $shortname = ParserUtil::skipFillerWsExpectToken($tokens, $i, T_STRING);
            $class = $terminatedNamespace . $shortname;
            yield SymbolHandle::fromClass($class) => RawSymbolInfo::forTopLevelSymbol($attrComments, $imports);

            // Get the full version of the tokens now.
            $tokenss->next();
            if ($tokenss->valid()) {
              // If not valid, $tokens already contains the complete file.
              $tokens = $tokenss->current();
            }

            $this->skipClassLikeExtendsImplements($tokens, $i);
            assert(ParserUtil::expect($tokens, $i, '{'));
            yield from $this->parseClassLikeBody($tokens, $i, $class);
            assert(ParserUtil::expect($tokens, $i, '}'));
            break;

          case T_STRING:
          case T_NS_SEPARATOR:
            // This could be the start of a method call. Ignore.
            break;

          default:
            // Ignore.
            break;
        }
      }
    }
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *
   * @return string
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  private function parseNamespace(array $tokens, int &$pos): string {
    assert(ParserUtil::expect($tokens, $pos, T_NAMESPACE));
    $i = $pos + 1;
    $namespace = ParserUtil::skipFillerWsExpectTString($tokens, $i);
    while (TRUE) {
      ++$i;
      if ($tokens[$i][0] !== T_NS_SEPARATOR) {
        break;
      }
      ++$i;
      if ($tokens[$i][0] !== T_STRING) {
        throw SyntaxException::expectedButFound($tokens, $i, 'T_STRING');
      }
      $namespace .= '\\' . $tokens[$i][1];
    }
    $id = ParserUtil::skipFillerWs($tokens, $i);
    if ($id !== ';') {
      if ($id === '{') {
        throw UnsupportedSyntaxException::fromTokenPos($tokens, $i, 'Nested namespace syntax is not supported.');
      }
      throw SyntaxException::expectedButFound($tokens, $i, ';');
    }
    $pos = $i;
    return $namespace;
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position of class name (T_STRING).
   *   After: Position at closing '}' of class body.
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  private function skipClassLikeExtendsImplements(array $tokens, int &$pos): void {
    assert(ParserUtil::expect($tokens, $pos, T_STRING));

    // Skip all extends and implements.
    for ($i = $pos + 1;; ++$i) {
      $token = $tokens[$i];
      if (is_string($token)) {
        switch ($token) {
          case ',':
            break;

          case '{':
            $pos = $i;
            return;

          default:
            throw SyntaxException::unexpected($tokens, $i, 'before class body');
        }
      }
      else {
        switch ($token[0]) {
          case T_EXTENDS:
          case T_IMPLEMENTS:
          case T_STRING:
          case T_NS_SEPARATOR:
          case T_COMMENT:
          case T_DOC_COMMENT:
          case T_WHITESPACE:
            break;

          default:
            throw SyntaxException::unexpected($tokens, $i, 'before class body');
        }
      }
    }
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   * @param string $class
   *
   * @return iterable<SymbolHandle, RawSymbolInfo>
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  private function parseClassLikeBody(array $tokens, int &$pos, string $class): iterable {
    assert(ParserUtil::expect($tokens, $pos, '{'));
    $attributeComments = [];
    for ($i = $pos + 1;; ++$i) {
      $token = $tokens[$i];
      if (is_string($token)) {
        switch ($token) {

          case '}':
            $pos = $i;
            return;

          case '?':
            // This is part of a type of a property or constant. Ignore.
            break;

          default:
            throw SyntaxException::unexpected($tokens, $i, 'in class body');
        }
      }
      else {
        switch ($token[0]) {
          case T_WHITESPACE:
            // Ignore. Don't clear attributes.
            continue 2;

          case T_DOC_COMMENT:
            // Ignore. Don't clear attributes.
            continue 2;

          case T_COMMENT:
            if (substr($token[1], 0, 2) === '#[') {
              // This is an attribute!
              $attributeComments[] = $token[1];
            }
            // Don't clear attributes.
            continue 2;

          case T_PRIVATE:
          case T_PUBLIC:
          case T_PROTECTED:
          case T_STATIC:
          case T_FINAL:
          case T_ABSTRACT:
            // Ignore modifiers. Don't clear attributes.
            continue 2;

          case T_CLASS:
          case T_INTERFACE:
          case T_TRAIT:
          case T_NAMESPACE:
            throw SyntaxException::unexpected($tokens, $i, 'in class scope.');

          case T_USE:
            // Ignore use traits. Do clear attributes.
            // Traits are already available via native reflection.
            $this->skipUseTraits($tokens, $i);
            \assert(ParserUtil::expectOneOf($tokens, $i, [';', '}']));
            break;

          case T_FUNCTION:
            $method = $this->parseFunctionHead($tokens, $i, TRUE);
            \assert($method !== NULL);
            $symbol = SymbolHandle::fromMethod($class, $method);
            yield $symbol => RawSymbolInfo::forInnerSymbol($attributeComments);
            foreach ($this->parseParams($tokens, $i) as $paramDollarName => $paramAttrComments) {
              $symbol = SymbolHandle::fromMethodParameter(
                $class,
                $method,
                substr($paramDollarName, 1));
              yield $symbol => RawSymbolInfo::forInnerSymbol($paramAttrComments);
            }
            \assert(ParserUtil::expect($tokens, $i, ')'));
            ++$i;
            $id = ParserUtil::skipFillerWs($tokens, $i);
            if ($id === ':') {
              $id = $this->skipReturnType($tokens, $i);
              \assert(ParserUtil::expectOneOf($tokens, $i, ['{', ';']));
            }
            if ($id === '{') {
              ParserUtil::skipSubtree($tokens, $i);
            }
            elseif ($id !== ';') {
              throw SyntaxException::expectedButFound($tokens, $i, '{ or ;');
            }
            break;

          case T_VARIABLE:
            $names = $this->parseClassPropertyGroup($tokens, $i);
            foreach ($names as $name) {
              yield SymbolHandle::fromClassProperty(
                $class,
                $name
              ) => RawSymbolInfo::forInnerSymbol($attributeComments);
            }
            break;

          case T_CONST:
            $names = $this->parseClassConstGroup($tokens, $i);
            foreach ($names as $name) {
              yield SymbolHandle::fromClassConstant(
                $class,
                $name
              ) => RawSymbolInfo::forInnerSymbol($attributeComments);
            }
            break;

          case \T_STRING:
          case \T_NS_SEPARATOR:
          case \T_ARRAY:
          case \T_CALLABLE:
            // This is part of a type of a property or constant. Ignore.
            continue 2;

          default:
            throw SyntaxException::unexpected($tokens, $i, 'in class body');
        }
      }
      $attributeComments = [];
    }
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position of T_FUNCTION.
   *   After: Position of '('.
   * @param bool $isClassMember
   *   TRUE if this is a class member.
   *
   * @return string
   *   Function shortname, or NULL if anonymous.
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  private function parseFunctionHead(array $tokens, int &$pos, bool $isClassMember = FALSE): ?string {
    assert(ParserUtil::expect($tokens, $pos, T_FUNCTION));

    $i = $pos + 1;
    $id = ParserUtil::skipFillerWs($tokens, $i);
    if ($id === '&') {
      ++$i;
      $id = ParserUtil::skipFillerWs($tokens, $i);
    }

    if ($id === '(') {
      // Anonymous function.
      if ($isClassMember) {
        throw SyntaxException::fromTokenPos($tokens, $i, 'Anonymous function in class not allowed.');
      }
      $pos = $i;
      return NULL;
    }

    if ($id !== T_STRING) {
      if (!$isClassMember) {
        throw SyntaxException::expectedButFound($tokens, $i, 'method name');
      }
      if (!\is_array($tokens[$i])
        || !ReservedWordUtil::validMemberName($tokens[$i][1])
      ) {
        throw SyntaxException::expectedButFound($tokens, $i, 'method name');
      }
    }

    $shortName = $tokens[$i][1];
    ++$i;
    ParserUtil::skipFillerWsExpectChar($tokens, $i, '(');
    $pos = $i;
    return $shortName;
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position at '(' before the parameters.
   *   After: Position at ')' after the parameters.
   *
   * @return iterable<string, string[]>
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  private function parseParams(array $tokens, int &$pos): iterable {
    assert(ParserUtil::expect($tokens, $pos, '('));
    /** @var string[] $attributeComments */
    $attributeComments = [];
    for ($i = $pos + 1;; ++$i) {
      $token = $tokens[$i];
      if (is_string($token)) {
        switch ($token) {

          case ')':
            $pos = $i;
            return;

          case '#':
            throw SyntaxException::fromTokenPos($tokens, $i, "Unexpected EOF in parameters.");

          case '&':
            // The parameter is by-reference. That's ok.
            break;

          case '?':
            // Found an optional type. Ignore.
            break;

          case '|':
            throw PhpVersionException::fromTokenPos($tokens, $i, 'Union types are only available in PHP 8.');

          default:
            throw SyntaxException::unexpected($tokens, $i, 'in parameters');
        }
      }
      else {
        switch ($token[0]) {
          case T_COMMENT:
            if (substr($token[1], 0, 2) === '#[') {
              // This is an attribute!
              $attributeComments[] = $token[1];
            }
            break;

          case T_VARIABLE:
            yield $token[1] => $attributeComments;
            $attributeComments = [];
            ++$i;
            $id = ParserUtil::skipHeaderWs($tokens, $i);
            if ($id === '=') {
              $id = $this->skipVarDefault($tokens, $i, TRUE);
            }
            // Skip until the comma or ')'.
            if ($id === ')') {
              $pos = $i;
              return;
            }
            if ($id !== ',') {
              throw SyntaxException::unexpected($tokens, $i, 'in parameters');
            }
            // Must be ','.
            break;

          default:
            // Ignore.
            break;
        }
      }
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
    assert(ParserUtil::expect($tokens, $pos, T_VARIABLE));
    $names = [substr($tokens[$pos][1], 1)];

    $i = $pos + 1;
    $id = ParserUtil::skipFillerWs($tokens, $i);
    while (true) {
      if ($id === '=') {
        $id = $this->skipVarDefault($tokens, $i, FALSE);
      }
      if ($id === ';') {
        $pos = $i;
        return $names;
      }
      // If it is not a ';', it must be a ',', according to the documented
      // behavior of ->skipVarDefault().
      \assert(ParserUtil::expect($tokens, $i, ','));
      ++$i;
      $id = ParserUtil::skipFillerWs($tokens, $i);
      if ($id !== T_VARIABLE) {
        throw SyntaxException::expectedButFound($tokens, $i, 'T_VARIABLE');
      }
      $names[] = substr($tokens[$i][1], 1);
      ++$i;
      $id = ParserUtil::skipFillerWs($tokens, $i);
    }
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
    assert(ParserUtil::expect($tokens, $pos, T_CONST));

    $i = $pos + 1;
    $names = [];
    $names[] = ParserUtil::skipFillerWsExpectMemberName($tokens, $i);

    ++$i;
    $id = ParserUtil::skipFillerWs($tokens, $i);
    while (true) {
      if ($id === '=') {
        $id = $this->skipVarDefault($tokens, $i, FALSE);
      }
      if ($id === ';') {
        $pos = $i;
        return $names;
      }
      // If it is not a ';', it must be a ',', according to the documented
      // behavior of ->skipVarDefault().
      \assert(ParserUtil::expect($tokens, $i, ','));
      ++$i;
      $id = ParserUtil::skipFillerWs($tokens, $i);
      if ($id !== T_STRING) {
        throw SyntaxException::expectedButFound($tokens, $i, 'T_STRING');
      }
      $names[] = $tokens[$i][1];
      ++$i;
      $id = ParserUtil::skipFillerWs($tokens, $i);
    }
  }

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
    assert(ParserUtil::expect($tokens, $pos, '='));
    for ($i = $pos + 1;; ++$i) {
      $token = $tokens[$i];
      if (!is_string($token)) {
        // Ignore any non-char tokens.+
        continue;
      }

      switch ($token) {
        case '(':
        case '{':
        case '[':
          ParserUtil::skipSubtree($tokens, $i);
          assert(ParserUtil::expectOneOf($tokens, $i, [')', '}', ']']));
          break;

        case ',':
          $pos = $i;
          return $token;

        case ')':
          if (!$isParam) {
            throw SyntaxException::unexpected($tokens, $i, 'after property or const default value');
          }
          $pos = $i;
          return $token;

        case ';':
          if ($isParam) {
            throw SyntaxException::unexpected($tokens, $i, 'after parameter default value');
          }
          $pos = $i;
          return $token;

        case '}':
        case ']':
          throw SyntaxException::fromTokenPos($tokens, $i, "Unexpected '$token' in parameters.");

        case '#':
          throw SyntaxException::fromTokenPos($tokens, $i, "Unexpected EOF in parameters.");

        default:
          break;
      }
    }
    // Silence Psalm.
    /** @noinspection PhpUnreachableStatementInspection */
    throw new \RuntimeException('Unreachable code.');
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position of 'use' statement.
   *   After (success): Directly on ';'.
   *   After (failure): Original position.
   * @param array<string, string> $imports
   *   Format: $[$alias] = $qcn.
   *
   * @return void
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  protected function parseImportGroup(array $tokens, int &$pos, array &$imports): void {
    assert(ParserUtil::expect($tokens, $pos, T_USE));
    $i = $pos + 1;
    $id = ParserUtil::skipFillerWs($tokens, $i);
    if ($id === T_CONST) {
      $type = 'const ';
      ++$i;
      $qcn = ParserUtil::skipFillerWsExpectToken($tokens, $i, T_STRING);
    }
    elseif ($id === T_FUNCTION) {
      $type = 'function ';
      ++$i;
      $qcn = ParserUtil::skipFillerWsExpectToken($tokens, $i, T_STRING);
    }
    elseif ($id === T_STRING) {
      $type = '';
      $qcn = $tokens[$i][1];
    }
    else {
      throw SyntaxException::unexpected($tokens, $i, 'in imports');
    }
    $first = TRUE;
    // Iterate over imports separated by comma.
    while (TRUE) {
      assert(ParserUtil::expect($tokens, $i, T_STRING));
      assert(preg_match('@^\w+$@', $qcn));
      // Iterate over QCN fragments separated by T_NS_SEPARATOR.
      while (TRUE) {
        assert(ParserUtil::expect($tokens, $i, T_STRING));
        assert(preg_match('@^\w+(?:\\\\\w+)*$@', $qcn));
        ++$i;
        if ($tokens[$i][0] !== T_NS_SEPARATOR) {
          break;
        }
        ++$i;
        if ($tokens[$i][0] !== T_STRING) {
          // This must be a curly group like `N\{A, B}`.
          if (!$first) {
            // A curly group can only exist within a single-element outer group.
            throw SyntaxException::expectedButFound($tokens, $i, 'T_STRING');
          }
          // The rest of the import statement is a curly group like `N{A, B}`.
          ParserUtil::skipFillerWsExpectChar($tokens, $i, '{');
          $this->parseImportCurlyGroup($tokens, $i, $imports, $qcn, $type);
          \assert(ParserUtil::expect($tokens, $i, '}'));
          ++$i;
          ParserUtil::skipFillerWsExpectChar($tokens, $i, ';');
          $pos = $i;
          return;
        }
        $qcn .= '\\' . $tokens[$i][1];
      }
      $alias = $type . $tokens[$i - 1][1];
      $id = ParserUtil::skipFillerWs($tokens, $i);
      if ($id === T_AS) {
        ++$i;
        $alias = $type . ParserUtil::skipFillerWsExpectToken($tokens, $i, T_STRING);
        ++$i;
        $id = ParserUtil::skipFillerWs($tokens, $i);
      }
      if (isset($imports[$alias])) {
        throw SyntaxException::fromTokenPos($tokens, $i, "Alias '$alias' already in use.");
      }
      $imports[$alias] = $qcn;
      if ($id === ';') {
        $pos = $i;
        return;
      }
      if ($id !== ',') {
        throw SyntaxException::unexpected($tokens, $i, 'in imports');
      }
      ++$i;
      $qcn = ParserUtil::skipFillerWsExpectToken($tokens, $i, T_STRING);
      $first = FALSE;
    }
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position of '{'.
   *   After: Position of closing '}'.
   * @param array<string, string> $imports
   *   Format: $[$alias] = $qcn.
   * @param string $qcn
   *   Qcn part from before the '{'.
   * @param string $type
   *   One of '', 'const ' or 'function ' (including the space).
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  private function parseImportCurlyGroup(array $tokens, int &$pos, array &$imports, string $qcn, string $type): void {
    $i = $pos;

    // Iterate over sub-imports within curly group.
    while (TRUE) {
      assert(ParserUtil::expectOneOf($tokens, $i, [',', '{']));
      ++$i;
      $id = ParserUtil::skipFillerWs($tokens, $i);
      if ($id === T_STRING) {
        $localType = $type;
        $subQcn = $tokens[$i][1];
      }
      elseif ($id === '}') {
        if (!isset($subQcn)) {
          throw SyntaxException::fromTokenPos($tokens, $i, 'Import group cannot be empty.');
        }
        $pos = $i;
        return;
      }
      elseif ($type !== '') {
        throw SyntaxException::unexpected($tokens, $i, 'in imports');
      }
      elseif ($id === T_CONST) {
        $localType = 'const ';
        ++$i;
        $subQcn = ParserUtil::skipFillerWsExpectToken($tokens, $i, T_STRING);
      }
      elseif ($id === T_FUNCTION) {
        $localType = 'function ';
        ++$i;
        $subQcn = ParserUtil::skipFillerWsExpectToken($tokens, $i, T_STRING);
      }
      else {
        throw SyntaxException::unexpected($tokens, $i, 'in imports');
      }
      assert(preg_match('@^\w+$@', $subQcn));
      assert(ParserUtil::expect($tokens, $i, T_STRING));
      assert(preg_match('@^\w+$@', $subQcn));
      // Iterate over fragments of QCN, separated by T_NS_SEPARATOR.
      while (TRUE) {
        assert(ParserUtil::expect($tokens, $i, T_STRING));
        assert(preg_match('@^\w+(?:\\\\\w+)*$@', $subQcn));
        ++$i;
        if ($tokens[$i][0] !== T_NS_SEPARATOR) {
          break;
        }
        ++$i;
        if ($tokens[$i][0] !== T_STRING) {
          throw SyntaxException::unexpected($tokens, $i, 'in imports');
        }
        $subQcn .= '\\' . $tokens[$i][1];
      }
      $alias = $localType . $tokens[$i - 1][1];
      $id = ParserUtil::skipFillerWs($tokens, $i);
      if ($id === T_AS) {
        ++$i;
        $alias = $localType . ParserUtil::skipFillerWsExpectTString($tokens, $i);
        ++$i;
        $id = ParserUtil::skipFillerWs($tokens, $i);
      }
      if (isset($imports[$alias])) {
        throw SyntaxException::fromTokenPos($tokens, $i, "Alias '$alias' already in use.");
      }
      $imports[$alias] = $qcn . '\\' . $subQcn;
      if ($id === '}') {
        $pos = $i;
        return;
      }
      if ($id !== ',') {
        throw SyntaxException::unexpected($tokens, $i, 'in imports');
      }
    }
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position of ':'.
   *   After: Position of '{' or ';'.
   *
   * @return string
   *   One of '{' or ';'.
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  private function skipReturnType(array $tokens, int &$pos): string {
    assert(ParserUtil::expect($tokens, $pos, ':'));
    for ($i = $pos + 1;; ++$i) {
      $token = $tokens[$i];
      if (\is_string($token)) {
        switch ($token) {
          case '{':
          case ';':
            $pos = $i;
            return $token;

          case '?':
            break;

          default:
            throw SyntaxException::unexpected($tokens, $i, 'in return type');
        }
      }
      else {
        switch ($token[0]) {
          case \T_NS_SEPARATOR:
          case \T_STRING:
          case \T_WHITESPACE:
          case \T_COMMENT:
          case \T_DOC_COMMENT:
          case \T_ARRAY:
          case \T_CALLABLE:
            break;

          default:
            throw SyntaxException::unexpected($tokens, $i, 'in return type');
        }
      }
    }
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position of T_USE.
   *   After: Position of ';' or '}'.
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  private function skipUseTraits(array $tokens, int &$pos): void {
    assert(ParserUtil::expect($tokens, $pos, T_USE));
    for ($i = $pos + 1;; ++$i) {
      $token = $tokens[$i];
      if (\is_string($token)) {
        switch ($token) {
          case '{':
            ParserUtil::skipSubtree($tokens, $i);
            \assert(ParserUtil::expect($tokens, $i, '}'));
            $pos = $i;
            return;

          case ';':
            $pos = $i;
            return;

          case ',':
            break;

          default:
            throw SyntaxException::unexpected($tokens, $i, 'in use trait statement');
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
            throw SyntaxException::unexpected($tokens, $i, 'in use trait statement');
        }
      }
    }
  }

}
