<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Parser;

use Donquixote\QuickAttributes\Builder\Value\ValueBuilderInterface;
use Donquixote\QuickAttributes\Exception\SyntaxException;
use Donquixote\QuickAttributes\Exception\UnsupportedSyntaxException;
use Donquixote\QuickAttributes\Util\ParserAssertUtil;
use Donquixote\QuickAttributes\Util\ParserUtil;

class FileTokenParser_Php8 extends FileTokenParser {

  /**
   * @inheritDoc
   */
  protected function parseNamespace(array $tokens, int &$pos): string {
    \assert(ParserAssertUtil::expect($tokens, $pos, \T_NAMESPACE));
    ++$pos;
    $id = ParserUtil::skipFillerWs($tokens, $pos);
    if ($id !== \T_STRING && $id !== \T_NAME_QUALIFIED) {
      throw SyntaxException::expectedButFound($tokens, $pos, 'T_STRING or T_NAME_QUALIFIED');
    }
    $namespace = $tokens[$pos][1];
    ++$pos;
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
   * @inheritDoc
   */
  protected function parseImportGroup(array $tokens, int &$pos, array &$imports): void {
    \assert(ParserAssertUtil::expect($tokens, $pos, \T_USE));
    ++$pos;
    $id = ParserUtil::skipFillerWs($tokens, $pos);
    if ($id === \T_CONST || $id === \T_FUNCTION) {
      $type = ($id === \T_CONST) ? 'const ' : 'function ';
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
      // Check if it is a FQN instead of QN.
      if ($id === \T_NAME_FULLY_QUALIFIED) {
        // Remove the leading ns separator.
        $qcn = \substr($tokens[$pos][1], 1);
      }
      elseif ($id === \T_NAME_QUALIFIED || $id === \T_STRING) {
        $qcn = $tokens[$pos][1];
      }
      else {
        throw SyntaxException::unexpected($tokens, $pos, 'in imports');
      }
      ++$pos;
      $id = ParserUtil::skipFillerWs($tokens, $pos);
      if ($id === \T_NS_SEPARATOR && $first) {
        // This must be a curly group like `N\{A, B}`.
        ++$pos;
        $id = ParserUtil::skipFillerWs($tokens, $pos);
        if ($id !== '{') {
          throw SyntaxException::expectedButFound($tokens, $pos, '{');
        }
        $this->parseImportCurlyGroup($tokens, $pos, $imports, $qcn, $type);
        ++$pos;
        ParserUtil::skipFillerWsExpectChar($tokens, $pos, ';');
        return;
      }
      if ($id === \T_AS) {
        ++$pos;
        $alias = $type . ParserUtil::skipFillerWsExpectTString($tokens, $pos);
        ++$pos;
        $id = ParserUtil::skipFillerWs($tokens, $pos);
      }
      elseif (false !== $nspos = \strrpos($qcn, '\\')) {
        $alias = $type . \substr($qcn, $nspos + 1);
      }
      else {
        $alias = $type . $qcn;
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
      if ($id === '}') {
        if (!isset($subQcn)) {
          throw SyntaxException::fromTokenPos($tokens, $pos, 'Import group cannot be empty.');
        }
        return;
      }
      if ($id === \T_CONST || $id === \T_FUNCTION) {
        if ($type !== '') {
          throw SyntaxException::unexpected($tokens, $pos, 'in imports');
        }
        $localType = ($id === \T_CONST) ? 'const ' : 'function ';
        ++$pos;
        $id = ParserUtil::skipFillerWs($tokens, $pos);
      }
      else {
        $localType = $type;
      }
      if ($id !== \T_STRING && $id !== \T_NAME_QUALIFIED) {
        throw SyntaxException::unexpected($tokens, $pos, 'in imports');
      }
      $subQcn = $tokens[$pos][1];
      ++$pos;
      $id = ParserUtil::skipFillerWs($tokens, $pos);
      if ($id === \T_AS) {
        ++$pos;
        $alias = $localType . ParserUtil::skipFillerWsExpectTString($tokens, $pos);
        ++$pos;
        $id = ParserUtil::skipFillerWs($tokens, $pos);
      }
      elseif (false !== $nspos = \strrpos($subQcn, '\\')) {
        $alias = $localType . \substr($subQcn, $nspos + 1);
      }
      else {
        $alias = $localType . $subQcn;
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
    \assert(\PHP_VERSION_ID >= 80000);
    if ($tokens[$pos][0] === \T_NAME_FULLY_QUALIFIED) {
      $fqcn = $tokens[$pos][1];
      ++$pos;
      /** @psalm-var class-string */
      return \substr($fqcn, 1);
    }
    if ($tokens[$pos][0] === \T_NAME_QUALIFIED) {
      $qcn = $tokens[$pos][1];
      ++$pos;
      $nspos = \strpos($qcn, '\\');
      \assert($nspos !== 0 && $nspos !== false);
      $alias = \substr($qcn, 0, $nspos);
      if (isset($this->imports[$alias])) {
        /** @psalm-var class-string */
        return $this->imports[$alias] . \substr($qcn, $nspos);
      }
      /** @psalm-var class-string */
      return $this->terminatedNamespace . $qcn;
    }
    if ($tokens[$pos][0] === \T_STRING) {
      $name = $tokens[$pos][1];
      ++$pos;
      /** @psalm-var class-string */
      return $this->imports[$name] ?? $this->terminatedNamespace . $name;
    }
    throw SyntaxException::expectedButFound($tokens, $pos, 'QCN or FQCN');
  }  // @codeCoverageIgnore

  /**
   * {@inheritdoc}
   */
  protected function parseConstRef(ValueBuilderInterface $builder, array $tokens, int &$pos) {
    \assert(\PHP_VERSION_ID >= 80000);
    $startpos = $pos;
    $tkFirst = $tokens[$startpos];
    $idFirst = $tkFirst[0];
    ++$pos;
    $idNext = ParserUtil::skipFillerWs($tokens, $pos);
    if ($idNext === '(') {
      // Function call.
      throw SyntaxException::fromTokenPos($tokens, $pos, 'Function call not allowed in constant expression.');
    }
    if ($idFirst === \T_NAME_FULLY_QUALIFIED) {
      $qn = \substr($tkFirst[1], 1);
    }
    elseif ($idFirst === \T_NAME_QUALIFIED) {
      $qnAlias = $tkFirst[1];
      $nspos = \strpos($qnAlias, '\\');
      \assert($nspos !== 0 && $nspos !== false);
      $alias = \substr($qnAlias, 0, $nspos);
      $qn = isset($this->imports[$alias])
        ? $this->imports[$alias] . \substr($qnAlias, $nspos)
        : $this->terminatedNamespace . $qnAlias;
    }
    else {
      \assert($idFirst === \T_STRING);
      $alias = $tkFirst[1];
      if ($idNext !== \T_DOUBLE_COLON) {
        // Global constant.
        if (isset($this->imports["const $alias"])) {
          $qn = $this->imports["const $alias"];
          $builder->setConstant($qn);
        }
        else {
          $builder->setConstant($this->terminatedNamespace . $alias, $alias);
        }
        return $idNext;
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
    }

    if ($idNext !== \T_DOUBLE_COLON) {
      // Global constant.
      $builder->setConstant($qn);
      return $idNext;
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
