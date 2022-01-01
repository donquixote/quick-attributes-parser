<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Parser;

use Donquixote\QuickAttributes\Exception\SyntaxException;
use Donquixote\QuickAttributes\Exception\UnsupportedSyntaxException;
use Donquixote\QuickAttributes\Util\ParserAssertUtil;
use Donquixote\QuickAttributes\Util\ParserUtil;

class FileParserPhp7 extends FileParser {

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
    $i = $pos + 1;
    $namespace = ParserUtil::skipFillerWsExpectTString($tokens, $i);
    while (true) {
      ++$i;
      if ($tokens[$i][0] !== \T_NS_SEPARATOR) {
        break;
      }
      ++$i;
      if ($tokens[$i][0] !== \T_STRING) {
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
    \assert(ParserAssertUtil::expect($tokens, $pos, \T_USE));
    $i = $pos + 1;
    $id = ParserUtil::skipFillerWs($tokens, $i);
    if ($id === \T_CONST) {
      $type = 'const ';
      ++$i;
      $id = ParserUtil::skipFillerWs($tokens, $i);
    }
    elseif ($id === \T_FUNCTION) {
      $type = 'function ';
      ++$i;
      $id = ParserUtil::skipFillerWs($tokens, $i);
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
        ++$i;
        // Don't support whitespace between namespace fragments.
        // Such whitespace is allowed in PHP 7, but not in PHP 8.
        $id = $tokens[$i][0];
      }
      // Read first part of QCN.
      if ($id !== \T_STRING) {
        throw SyntaxException::unexpected($tokens, $i, 'in imports');
      }
      $qcn = $tokens[$i][1];
      // Iterate over QCN fragments separated by T_NS_SEPARATOR.
      while (true) {
        \assert(ParserAssertUtil::expect($tokens, $i, \T_STRING));
        \assert(\preg_match('@^\w+(?:\\\\\w+)*$@', $qcn));
        ++$i;
        if ($tokens[$i][0] !== \T_NS_SEPARATOR) {
          break;
        }
        ++$i;
        if ($tokens[$i][0] !== \T_STRING) {
          // This must be a curly group like `N\{A, B}`.
          if (!$first) {
            // A curly group can only exist within a single-element outer group.
            throw SyntaxException::expectedButFound($tokens, $i, 'T_STRING');
          }
          // The rest of the import statement is a curly group like `N{A, B}`.
          ParserUtil::skipFillerWsExpectChar($tokens, $i, '{');
          $this->parseImportCurlyGroup($tokens, $i, $imports, $qcn, $type);
          \assert(ParserAssertUtil::expect($tokens, $i, '}'));
          ++$i;
          ParserUtil::skipFillerWsExpectChar($tokens, $i, ';');
          $pos = $i;
          return;
        }
        $qcn .= '\\' . $tokens[$i][1];
      }
      $alias = $type . $tokens[$i - 1][1];
      $id = ParserUtil::skipFillerWs($tokens, $i);
      if ($id === \T_AS) {
        ++$i;
        $alias = $type . ParserUtil::skipFillerWsExpectToken($tokens, $i, \T_STRING);
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
      $id = ParserUtil::skipFillerWs($tokens, $i);
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
    $i = $pos;

    // Iterate over sub-imports within curly group.
    while (true) {
      \assert(ParserAssertUtil::expectOneOf($tokens, $i, [',', '{']));
      ++$i;
      $id = ParserUtil::skipFillerWs($tokens, $i);
      if ($id === \T_STRING) {
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
      elseif ($id === \T_CONST) {
        $localType = 'const ';
        ++$i;
        $subQcn = ParserUtil::skipFillerWsExpectToken($tokens, $i, \T_STRING);
      }
      elseif ($id === \T_FUNCTION) {
        $localType = 'function ';
        ++$i;
        $subQcn = ParserUtil::skipFillerWsExpectToken($tokens, $i, \T_STRING);
      }
      else {
        throw SyntaxException::unexpected($tokens, $i, 'in imports');
      }
      \assert(\preg_match('@^\w+$@', $subQcn));
      \assert(ParserAssertUtil::expect($tokens, $i, \T_STRING));
      \assert(\preg_match('@^\w+$@', $subQcn));
      // Iterate over fragments of QCN, separated by T_NS_SEPARATOR.
      while (true) {
        \assert(ParserAssertUtil::expect($tokens, $i, \T_STRING));
        \assert(\preg_match('@^\w+(?:\\\\\w+)*$@', $subQcn));
        ++$i;
        if ($tokens[$i][0] !== \T_NS_SEPARATOR) {
          break;
        }
        ++$i;
        if ($tokens[$i][0] !== \T_STRING) {
          throw SyntaxException::unexpected($tokens, $i, 'in imports');
        }
        $subQcn .= '\\' . $tokens[$i][1];
      }
      $alias = $localType . $tokens[$i - 1][1];
      $id = ParserUtil::skipFillerWs($tokens, $i);
      if ($id === \T_AS) {
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
  }  // @codeCoverageIgnore

}
