<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Parser;

use Donquixote\QuickAttributes\Exception\SyntaxException;
use Donquixote\QuickAttributes\Exception\UnsupportedSyntaxException;
use Donquixote\QuickAttributes\Util\ParserUtil;
use Donquixote\QuickAttributes\Util\TokenizerUtil;
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

    $php = file_get_contents($file);

    return $this->parseFilePhp($php);
  }

  /**
   * @param string $php
   *   PHP from a file.
   *
   * @return iterable<SymbolHandle, RawSymbolInfo>
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function parseFilePhp(string $php): iterable {

    $tokenss = TokenizerUtil::tokenizeClassFileContents($php);

    // Forget the file contents to save memory.
    // This allows more iterators to stay in memory at the same time.
    unset($php);

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
          case '{':
          case '[':
            ParserUtil::skipSubtree($tokens, $i);
            break;

          case '"':
            ParserUtil::skipDoubleQuotedString($tokens, $i);
            break;

          case ')':
          case '}':
          case ']':
            throw new SyntaxException("Unexpected '$token' in file scope.");

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
            if ($shortname === NULL) {
              // Anonymous function. Ignore whatever comes next.
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
              $tokens = $tokenss->current();
            }

            $this->skipClassLikeExtendsImplements($tokens, $i);
            assert(ParserUtil::expect($tokens, $i, '{'));
            yield from $this->parseClassLikeBody($tokens, $i, $class);
            assert(ParserUtil::expect($tokens, $i, '}'));
            break;

          case T_STRING:
          case T_NS_SEPARATOR:
            // Ignore.
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
          case '(':
          case '{':
          case '[':
            ParserUtil::skipSubtree($tokens, $i);
            break;

          case '"':
            ParserUtil::skipDoubleQuotedString($tokens, $i);
            break;

          case ')':
          case ']':
          case '#':
            throw SyntaxException::unexpected($tokens, $i, 'in class body');

          case '}':
            $pos = $i;
            return;

          default:
            # $scope->clear($token);
            # break;
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
            break;

          case T_FUNCTION:
            $method = $this->parseFunctionHead($tokens, $i);
            if ($method === NULL) {
              throw SyntaxException::fromTokenPos($tokens, $i, 'Anonymous function is not allowed here.');
            }
            $symbol = SymbolHandle::fromMethod($class, $method);
            yield $symbol => RawSymbolInfo::forInnerSymbol($attributeComments);
            foreach ($this->parseParams($tokens, $i) as $paramDollarName => $paramAttrComments) {
              $symbol = SymbolHandle::fromMethodParameter(
                $class,
                $method,
                substr($paramDollarName, 1));
              yield $symbol => RawSymbolInfo::forInnerSymbol($paramAttrComments);
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

          case T_STRING:
          case T_NS_SEPARATOR:
            // Ignore.
            continue 2;

          default:
            // Ignore.
            continue 2;
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
   *
   * @return string
   *   Function shortname, or NULL if anonymous.
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  private function parseFunctionHead(array $tokens, int &$pos): ?string {
    assert(ParserUtil::expect($tokens, $pos, T_FUNCTION));

    $i = $pos + 1;
    $id = ParserUtil::skipFillerWs($tokens, $i);
    if ($id === '&') {
      ++$i;
      $id = ParserUtil::skipFillerWs($tokens, $i);
    }

    if ($id === T_STRING) {
      $shortName = $tokens[$i][1];
      ++$i;
      ParserUtil::skipFillerWsExpectChar($tokens, $i, '(');
      $pos = $i;
      return $shortName;
    }

    if ($id === '(') {
      // Anonymous function.
      $pos = $i;
      return NULL;
    }

    throw SyntaxException::expectedButFound($tokens, $i, '(');
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
        // Clear attribute comments whenever we hit a plain char symbol.
        $attributeComments = [];
        switch ($token) {
          case '(':
          case '{':
          case '[':
            ParserUtil::skipSubtree($tokens, $i);
            break;

          case '"':
            ParserUtil::skipDoubleQuotedString($tokens, $i);
            break;

          case ')':
            $pos = $i;
            return;

          case '}':
          case ']':
            throw SyntaxException::fromTokenPos($tokens, $i, "Unexpected '$token' in parameters.");

          case '#':
            throw SyntaxException::fromTokenPos($tokens, $i, "Unexpected EOF in parameters.");

          default:
            break;
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
      if ($id !== ',') {
        throw SyntaxException::unexpected($tokens, $i, 'in property declaration');
      }
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
    $names[] = ParserUtil::skipFillerWsExpectToken($tokens, $i, T_STRING);

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
      if ($id !== ',') {
        throw SyntaxException::unexpected($tokens, $i, 'in constant declaration');
      }
      $id = ParserUtil::skipFillerWs($tokens, $i);
      if ($id !== T_STRING) {
        throw SyntaxException::expectedButFound($tokens, $i, 'T_VARIABLE');
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
   *   Symbol that follows the default value. One of ')', ';', ','.
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  private function skipVarDefault(array $tokens, int &$pos, bool $isParam): string {
    assert(ParserUtil::expect($tokens, $pos, '='));
    $i = $pos + 1;
    while (true) {
      $token = $tokens[$i];
      if (!is_string($token)) {
        // Ignore any non-char tokens.
        ++$i;
        continue;
      }

      switch ($token) {
        case '(':
        case '{':
        case '[':
          ParserUtil::skipSubtree($tokens, $i);
          continue 2;

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
          ++$i;
          continue 2;
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
    elseif ($id !== T_STRING) {
      throw SyntaxException::unexpected($tokens, $i, 'in imports');
    }
    else {
      $type = '';
      $qcn = $tokens[$i][1];
    }
    $first = TRUE;
    while (TRUE) {
      assert(ParserUtil::expect($tokens, $i, T_STRING));
      assert(preg_match('@^\w+$@', $qcn));
      while (TRUE) {
        assert(ParserUtil::expect($tokens, $i, T_STRING));
        assert(preg_match('@^\w+(?:\\\\\w+)*$@', $qcn));
        ++$i;
        if ($tokens[$i][0] !== T_NS_SEPARATOR) {
          break;
        }
        ++$i;
        if ($tokens[$i][0] !== T_STRING) {
          if (!$first) {
            throw SyntaxException::expectedButFound($tokens, $i, 'T_STRING');
          }
          break 2;
        }
        $qcn .= '\\' . $tokens[$i][1];
      }
      $alias = $tokens[$i - 1][1];
      $id = ParserUtil::skipFillerWs($tokens, $i);
      if ($id === T_AS) {
        ++$i;
        $alias = ParserUtil::skipFillerWsExpectToken($tokens, $i, T_STRING);
        if (isset($imports[$alias])) {
          throw SyntaxException::fromTokenPos($tokens, $i, "Alias '$alias' already in use.");
        }
        ++$i;
        $id = ParserUtil::skipFillerWs($tokens, $i);
      }
      $imports[$type . $alias] = $qcn;
      if ($id === ';') {
        $pos = $i;
        return;
      }
      if ($id !== ',') {
        throw SyntaxException::unexpected($tokens, $i, 'in imports');
      }
      $qcn = ParserUtil::skipFillerWsExpectToken($tokens, $i, T_STRING);
      $first = FALSE;
    }

    assert(ParserUtil::expect($tokens, $i - 1, T_NS_SEPARATOR));
    assert($tokens[$i][0] !== T_STRING);

    ParserUtil::skipFillerWsExpectChar($tokens, $i, '{');

    $subQcn = ParserUtil::skipFillerWsExpectToken($tokens, $i, T_STRING);

    while (TRUE) {
      assert(ParserUtil::expect($tokens, $i, T_STRING));
      assert(preg_match('@^\w+$@', $subQcn));
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
      $alias = $tokens[$i - 1][1];
      $id = ParserUtil::skipFillerWs($tokens, $i);
      if ($id === T_AS) {
        ++$i;
        $alias = ParserUtil::skipFillerWsExpectToken($tokens, $i, T_STRING);
        if (isset($imports[$alias])) {
          throw SyntaxException::fromTokenPos($tokens, $i, "Alias '$alias' already in use.");
        }
        $id = ParserUtil::skipFillerWs($tokens, $i);
      }
      $imports[$type . $alias] = $qcn . '\\' . $subQcn;
      if ($id === '}') {
        ParserUtil::skipFillerWsExpectChar($tokens, $i, ';');
        $pos = $i;
        return;
      }
      if ($id !== ',') {
        throw SyntaxException::unexpected($tokens, $i, 'in imports');
      }
      $subQcn = ParserUtil::skipFillerWsExpectToken($tokens, $i, T_STRING);
    }
  }

}
