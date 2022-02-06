<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Parser;

use Donquixote\QuickAttributes\Builder\Value\ValueBuilderInterface;
use Donquixote\QuickAttributes\Exception\SyntaxException;
use Donquixote\QuickAttributes\Exception\UnsupportedSyntaxException;
use Donquixote\QuickAttributes\Util\ParserAssertUtil;
use Donquixote\QuickAttributes\Util\ParserUtil;

class FileTokenParser_Php7 extends FileTokenParser {

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
  protected function parseNamespace(array $tokens, int &$pos): string {
    \assert(ParserAssertUtil::expect($tokens, $pos, \T_NAMESPACE));
    ++$pos;
    $namespace = ParserUtil::skipFillerWsExpectTString($tokens, $pos);
    while (true) {
      ++$pos;
      if ($tokens[$pos][0] !== \T_NS_SEPARATOR) {
        break;
      }
      ++$pos;
      if ($tokens[$pos][0] !== \T_STRING) {
        throw SyntaxException::expectedButFound($tokens, $pos, 'T_STRING');
      }
      $namespace .= '\\' . $tokens[$pos][1];
    }
    $id = ParserUtil::skipFillerWs($tokens, $pos);
    if ($id !== ';') {
      if ($id === '{') {
        throw UnsupportedSyntaxException::fromTokenPos($tokens, $pos, 'Nested namespace syntax is not supported.');
      }
      throw SyntaxException::expectedButFound($tokens, $pos, ';');
    }
    return $namespace;
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
  protected function parseImportGroup(array $tokens, int &$pos, array &$imports): void {
    \assert(ParserAssertUtil::expect($tokens, $pos, \T_USE));
    ++$pos;
    $id = ParserUtil::skipFillerWs($tokens, $pos);
    if ($id === \T_CONST) {
      $type = 'const ';
      ++$pos;
      $id = ParserUtil::skipFillerWs($tokens, $pos);
    }
    elseif ($id === \T_FUNCTION) {
      $type = 'function ';
      ++$pos;
      $id = ParserUtil::skipFillerWs($tokens, $pos);
    }
    else {
      $type = '';
    }
    $first = true;
    // Iterate over imports separated by comma.
    while (true) {
      // Read the QN or FQN.
      if ($id === \T_NS_SEPARATOR) {
        // This is a FQN, not a QN. Skip the leading ns separator.
        ++$pos;
        // Don't support whitespace between namespace fragments.
        // Such whitespace is allowed in PHP 7, but not in PHP 8.
        $id = $tokens[$pos][0];
      }
      // Read first part of QCN.
      if ($id !== \T_STRING) {
        throw SyntaxException::unexpected($tokens, $pos, 'in imports');
      }
      $qcn = $tokens[$pos][1];
      // Iterate over QCN fragments separated by T_NS_SEPARATOR.
      while (true) {
        \assert(ParserAssertUtil::expect($tokens, $pos, \T_STRING));
        \assert(\preg_match('@^\w+(?:\\\\\w+)*$@', $qcn));
        ++$pos;
        if ($tokens[$pos][0] !== \T_NS_SEPARATOR) {
          break;
        }
        ++$pos;
        if ($tokens[$pos][0] !== \T_STRING) {
          // This must be a curly group like `N\{A, B}`.
          if (!$first) {
            // A curly group can only exist within a single-element outer group.
            throw SyntaxException::expectedButFound($tokens, $pos, 'T_STRING');
          }
          // The rest of the import statement is a curly group like `N{A, B}`.
          ParserUtil::skipFillerWsExpectChar($tokens, $pos, '{');
          $this->parseImportCurlyGroup($tokens, $pos, $imports, $qcn, $type);
          \assert(ParserAssertUtil::expect($tokens, $pos, '}'));
          ++$pos;
          ParserUtil::skipFillerWsExpectChar($tokens, $pos, ';');
          return;
        }
        $qcn .= '\\' . $tokens[$pos][1];
      }
      $alias = $type . $tokens[$pos - 1][1];
      $id = ParserUtil::skipFillerWs($tokens, $pos);
      if ($id === \T_AS) {
        ++$pos;
        $alias = $type . ParserUtil::skipFillerWsExpectTString($tokens, $pos);
        ++$pos;
        $id = ParserUtil::skipFillerWs($tokens, $pos);
      }
      if (isset($imports[$alias])) {
        throw SyntaxException::fromTokenPos($tokens, $pos, "Alias '$alias' already in use.");
      }
      $imports[$alias] = $qcn;
      if ($id === ';') {
        return;
      }
      if ($id !== ',') {
        throw SyntaxException::unexpected($tokens, $pos, 'in imports');
      }
      ++$pos;
      $id = ParserUtil::skipFillerWs($tokens, $pos);
      $first = false;
    }
  }  // @codeCoverageIgnore

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

    // Iterate over sub-imports within curly group.
    while (true) {
      \assert(ParserAssertUtil::expectOneOf($tokens, $pos, [',', '{']));
      ++$pos;
      $id = ParserUtil::skipFillerWs($tokens, $pos);
      if ($id === \T_STRING) {
        $localType = $type;
        $subQcn = $tokens[$pos][1];
      }
      elseif ($id === '}') {
        if (!isset($subQcn)) {
          throw SyntaxException::fromTokenPos($tokens, $pos, 'Import group cannot be empty.');
        }
        return;
      }
      elseif ($type !== '') {
        throw SyntaxException::unexpected($tokens, $pos, 'in imports');
      }
      elseif ($id === \T_CONST) {
        $localType = 'const ';
        ++$pos;
        $subQcn = ParserUtil::skipFillerWsExpectTString($tokens, $pos);
      }
      elseif ($id === \T_FUNCTION) {
        $localType = 'function ';
        ++$pos;
        $subQcn = ParserUtil::skipFillerWsExpectTString($tokens, $pos);
      }
      else {
        throw SyntaxException::unexpected($tokens, $pos, 'in imports');
      }
      \assert(\preg_match('@^\w+$@', $subQcn));
      \assert(ParserAssertUtil::expect($tokens, $pos, \T_STRING));
      \assert(\preg_match('@^\w+$@', $subQcn));
      // Iterate over fragments of QCN, separated by T_NS_SEPARATOR.
      while (true) {
        \assert(ParserAssertUtil::expect($tokens, $pos, \T_STRING));
        \assert(\preg_match('@^\w+(?:\\\\\w+)*$@', $subQcn));
        ++$pos;
        if ($tokens[$pos][0] !== \T_NS_SEPARATOR) {
          break;
        }
        ++$pos;
        if ($tokens[$pos][0] !== \T_STRING) {
          throw SyntaxException::unexpected($tokens, $pos, 'in imports');
        }
        $subQcn .= '\\' . $tokens[$pos][1];
      }
      $alias = $localType . $tokens[$pos - 1][1];
      $id = ParserUtil::skipFillerWs($tokens, $pos);
      if ($id === \T_AS) {
        ++$pos;
        $alias = $localType . ParserUtil::skipFillerWsExpectTString($tokens, $pos);
        ++$pos;
        $id = ParserUtil::skipFillerWs($tokens, $pos);
      }
      if (isset($imports[$alias])) {
        throw SyntaxException::fromTokenPos($tokens, $pos, "Alias '$alias' already in use.");
      }
      $imports[$alias] = $qcn . '\\' . $subQcn;
      if ($id === '}') {
        return;
      }
      if ($id !== ',') {
        throw SyntaxException::unexpected($tokens, $pos, 'in imports');
      }
    }
  }  // @codeCoverageIgnore

  /**
   * {@inheritdoc}
   */
  protected function parseAttributeName(array $tokens, int &$pos): string {
    \assert(\PHP_VERSION_ID < 80000);
    if ($tokens[$pos][0] === \T_NS_SEPARATOR) {
      ++$pos;
      if ($tokens[$pos][0] !== \T_STRING) {
        throw SyntaxException::expectedButFound($tokens, $pos, 'T_STRING');
      }
      $qcn = $tokens[$pos][1];
      ++$pos;
    }
    elseif ($tokens[$pos][0] === \T_STRING) {
      $name = $tokens[$pos][1];
      $qcn = $this->imports[$name] ?? $this->terminatedNamespace . $name;
      ++$pos;
    }
    else {
      throw SyntaxException::expectedButFound($tokens, $pos, 'QCN or FQCN');
    }
    if ($tokens[$pos][0] !== \T_NS_SEPARATOR) {
      /** @var class-string $qcn */
      return $qcn;
    }
    // Parse remaining part of the QCN.
    while (true) {
      ++$pos;
      if ($tokens[$pos][0] !== \T_STRING) {
        throw SyntaxException::expectedButFound($tokens, $pos, 'T_STRING');
      }
      $qcn .= '\\' . $tokens[$pos][1];
      ++$pos;
      if ($tokens[$pos][0] !== \T_NS_SEPARATOR) {
        /** @var class-string $qcn */
        return $qcn;
      }
    }
  }  // @codeCoverageIgnore

  /**
   * {@inheritdoc}
   */
  protected function parseConstRef(ValueBuilderInterface $builder, array $tokens, int &$pos) {
    \assert(\PHP_VERSION_ID < 80000);
    $startpos = $pos;
    if ($tokens[$pos][0] === \T_STRING) {
      // Found a non-fully-qualified name like "aa" or "aa\\bb\\cc".
      $alias = $tokens[$pos][1];
      ++$pos;
      if ($tokens[$pos][0] === \T_NS_SEPARATOR) {
        // Found a non-fully-qualified name with namespace, like "aa\\bb\\cc".
        // Resolve namespace alias.
        $qn = $this->imports[$alias] ?? ($this->terminatedNamespace . $alias);
        unset($alias);
        // Append remaining parts of the QN.
        while (true) {
          ++$pos;
          if ($tokens[$pos][0] !== \T_STRING) {
            throw SyntaxException::expectedButFound($tokens, $pos, 'T_STRING');
          }
          $qn .= '\\' . $tokens[$pos][1];
          ++$pos;
          if ($tokens[$pos][0] !== \T_NS_SEPARATOR) {
            // The non-fully-qualified name ends here.
            break;
          }
        }
        // Find the next non-whitespace after the QN.
        $id = ParserUtil::skipFillerWs($tokens, $pos);
        if ($id === '(') {
          throw SyntaxException::fromTokenPos($tokens, $pos, 'Function call not allowed in constant expression.');
        }
        if ($id !== \T_DOUBLE_COLON) {
          $builder->setConstant($qn);
          return $id;
        }
      }
      else {
        // Found a non-fully-qualified name without namespace, like "aa".
        // Find the next non-whitespace after the name.
        $id = ParserUtil::skipFillerWs($tokens, $pos);
        if ($id === '(') {
          throw SyntaxException::fromTokenPos($tokens, $pos, 'Function call not allowed in constant expression.');
        }
        if ($id !== \T_DOUBLE_COLON) {
          if (isset($this->imports["const $alias"])) {
            $qn = $this->imports["const $alias"];
            $builder->setConstant($qn);
          }
          else {
            $builder->setConstant($this->terminatedNamespace . $alias, $alias);
          }
          return $id;
        }

        // Class constant or ::class expression.
        if (\strtolower($alias) === 'self') {
          if ($this->class === null) {
            throw SyntaxException::unexpected($tokens, $startpos, 'outside of class context');
          }
          $qn = $this->class;
        }
        else {
          $qn = $this->imports[$alias] ?? $this->terminatedNamespace . $alias;
        }

        unset($alias);
      }
    }
    else {
      // Found a fully-qualified name like "\\aa" or "\\aa\\bb".
      \assert($tokens[$pos][0] === \T_NS_SEPARATOR);
      ++$pos;
      if ($tokens[$pos][0] !== \T_STRING) {
        throw SyntaxException::expectedButFound($tokens, $pos, 'T_STRING');
      }
      $qn = $tokens[$pos][1];
      while (true) {
        ++$pos;
        if ($tokens[$pos][0] !== \T_NS_SEPARATOR) {
          // The fully-qualified name ends here.
          break;
        }
        ++$pos;
        if ($tokens[$pos][0] !== \T_STRING) {
          throw SyntaxException::expectedButFound($tokens, $pos, 'T_STRING');
        }
        $qn .= '\\' . $tokens[$pos][1];
      }
      // Find the next non-whitespace after the FQN.
      $id = ParserUtil::skipFillerWs($tokens, $pos);
    }

    /** @var string|int $id */
    if ($id !== \T_DOUBLE_COLON) {
      // Global constant.
      $builder->setConstant($qn);
      return $id;
    }

    // Fqn refers to a class.
    ++$pos;
    $id = ParserUtil::skipFillerWs($tokens, $pos);
    if ($id === \T_CLASS) {
      $builder->setFixedValue($qn);
    }
    elseif ($id === \T_STRING) {
      $builder->setConstant($qn . '::' . $tokens[$pos][1]);
    }
    else {
      throw SyntaxException::expectedButFound($tokens, $pos, 'T_STRING or T_CLASS');
    }
    ++$pos;
    return ParserUtil::skipFillerWs($tokens, $pos);
  }

}
