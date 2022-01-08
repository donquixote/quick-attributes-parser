<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Parser;

use Donquixote\QuickAttributes\Exception\SyntaxException;
use Donquixote\QuickAttributes\Exception\UnsupportedSyntaxException;
use Donquixote\QuickAttributes\Util\ParserAssertUtil;
use Donquixote\QuickAttributes\Util\ParserUtil;

class FileParserPhp8 extends FileParser {

  /**
   * @inheritDoc
   */
  protected function parseNamespace(array $tokens, int &$pos): string {
    \assert(ParserAssertUtil::expect($tokens, $pos, \T_NAMESPACE));
    $i = $pos + 1;
    $id = ParserUtil::skipFillerWs($tokens, $i);
    if ($id !== \T_STRING && $id !== \T_NAME_QUALIFIED) {
      throw SyntaxException::expectedButFound($tokens, $pos, 'T_STRING or T_NAME_QUALIFIED');
    }
    $namespace = $tokens[$i][1];
    ++$i;
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
   * @inheritDoc
   */
  protected function parseImportGroup(array $tokens, int &$pos, array &$imports): void {
    \assert(ParserAssertUtil::expect($tokens, $pos, \T_USE));
    $i = $pos + 1;
    $id = ParserUtil::skipFillerWs($tokens, $i);
    if ($id === \T_CONST || $id === \T_FUNCTION) {
      $type = ($id === \T_CONST) ? 'const ' : 'function ';
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
      // Check if it is a FQN instead of QN.
      if ($id === \T_NAME_FULLY_QUALIFIED) {
        // Remove the leading ns separator.
        $qcn = \substr($tokens[$i][1], 1);
      }
      elseif ($id === \T_NAME_QUALIFIED || $id === \T_STRING) {
        $qcn = $tokens[$i][1];
      }
      else {
        throw SyntaxException::unexpected($tokens, $i, 'in imports');
      }
      ++$i;
      $id = ParserUtil::skipFillerWs($tokens, $i);
      if ($id === \T_NS_SEPARATOR) {
        // This must be a curly group like `N\{A, B}`.
        if (!$first) {
          // A curly group can only exist within a single-element outer group.
          throw SyntaxException::expectedButFound($tokens, $i, 'T_STRING');
        }
        ++$i;
        $id = ParserUtil::skipFillerWs($tokens, $i);
        if ($id !== '{') {
          throw SyntaxException::expectedButFound($tokens, $i, '{');
        }
        $this->parseImportCurlyGroup($tokens, $i, $imports, $qcn, $type);
        ++$i;
        ParserUtil::skipFillerWsExpectChar($tokens, $i, ';');
        $pos = $i;
        return;
      }
      $id = ParserUtil::skipFillerWs($tokens, $i);
      if ($id === \T_AS) {
        ++$i;
        $alias = $type . ParserUtil::skipFillerWsExpectTString($tokens, $i);
        ++$i;
        $id = ParserUtil::skipFillerWs($tokens, $i);
      }
      elseif (false !== $nspos = \strrpos($qcn, '\\')) {
        $alias = $type . \substr($qcn, $nspos + 1);
      }
      else {
        $alias = $type . $qcn;
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
      if ($id === '}') {
        if (!isset($subQcn)) {
          throw SyntaxException::fromTokenPos($tokens, $i, 'Import group cannot be empty.');
        }
        $pos = $i;
        return;
      }
      if ($id === \T_CONST || $id === \T_FUNCTION) {
        if ($type !== '') {
          throw SyntaxException::unexpected($tokens, $i, 'in imports');
        }
        $localType = ($id === \T_CONST) ? 'const ' : 'function ';
        ++$i;
        $id = ParserUtil::skipFillerWs($tokens, $i);
      }
      else {
        $localType = $type;
      }
      if ($id !== \T_STRING && $id !== \T_NAME_QUALIFIED) {
        throw SyntaxException::unexpected($tokens, $i, 'in imports');
      }
      $subQcn = $tokens[$i][1];
      ++$i;
      $id = ParserUtil::skipFillerWs($tokens, $i);
      if ($id === \T_AS) {
        ++$i;
        $alias = $localType . ParserUtil::skipFillerWsExpectTString($tokens, $i);
        ++$i;
        $id = ParserUtil::skipFillerWs($tokens, $i);
      }
      elseif (false !== $nspos = \strrpos($subQcn, '\\')) {
        $alias = $localType . \substr($subQcn, $nspos + 1);
      }
      else {
        $alias = $localType . $subQcn;
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
