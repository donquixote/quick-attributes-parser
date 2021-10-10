<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\AttributeCommentParser;

use Donquixote\QuickAttributes\Exception\SyntaxException;
use Donquixote\QuickAttributes\RawAttribute\RawAttribute_Eval;
use Donquixote\QuickAttributes\RawAttribute\RawAttribute_NoArgs;
use Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface;
use Donquixote\QuickAttributes\Util\ParserUtil;

class AttributeCommentParser implements AttributeCommentParserInterface {

  private string $terminatedNamespace = '';

  /**
   * @var array<string, string>
   */
  private array $imports = [];

  /**
   * @var class-string|null
   */
  private ?string $class = NULL;

  /**
   * @param string|null $namespace
   * @param array<string, string> $imports
   * @param class-string|null $class
   *
   * @return static
   */
  public function withContext(?string $namespace, array $imports, ?string $class): self {
    assert($namespace !== '');
    assert($namespace === NULL || $namespace[0] !== '\\');
    $clone = clone $this;
    $clone->terminatedNamespace = ($namespace === NULL) ? '' : ($namespace . '\\');
    $clone->imports = $imports;
    $clone->class = $class;
    return $clone;
  }

  /**
   * {@inheritdoc}
   */
  public function parse(string $comment): array {
    $rawAttributes = [];
    while (TRUE) {
      foreach ($this->doParse($comment, $next) as $rawAttribute) {
        $rawAttributes[] = $rawAttribute;
      }
      if ($next === NULL) {
        break;
      }
      assert($comment !== $next);
      $comment = $next;
    }
    return $rawAttributes;
  }

  /**
   * @param string $comment
   * @param string|null $next
   *
   * @return iterable<int, RawAttributeInterface>
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  private function doParse(string $comment, ?string &$next): iterable {
    if (\substr($comment, 0, 2) !== '#[') {
      throw new \InvalidArgumentException('Comment must begin with #[.');
    }
    $php = '<?php [' . \substr($comment, 2);
    $tokens = token_get_all($php);
    // Add an EOF marker.
    $tokens[] = '#';
    assert($tokens[0][0] === T_OPEN_TAG);
    assert($tokens[1] === '[');
    $i = 1;
    yield from $this->parseAttributes($tokens, $i);
    assert(ParserUtil::expect($tokens, $i, ']'));
    ++$i;
    $id = ParserUtil::skipFillerWs($tokens, $i);
    if ($id === '#') {
      // EOF reached.
      $next = NULL;
      return;
    }
    if ($id !== T_COMMENT) {
      throw SyntaxException::expectedButFound($tokens, $i, 'T_COMMENT or EOF');
    }
    assert(count($tokens) === $i + 2);
    $next = $tokens[$i][1];
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position of '['.
   *   After: Position of ']'.
   *
   * @return iterable<int, RawAttributeInterface>
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  private function parseAttributes(array $tokens, int &$pos): iterable {
    assert(ParserUtil::expect($tokens, $pos, '['));
    $i = $pos;
    while (TRUE) {
      assert(ParserUtil::expectOneOf($tokens, $i, ['[', ',']));
      ++$i;
      ParserUtil::skipFillerWs($tokens, $i);
      $iBkp0 = $i;
      $qcn = $this->parseClassRef($tokens, $i);
      assert($i > $iBkp0);
      $id = ParserUtil::skipFillerWs($tokens, $i);
      assert(ParserUtil::expectOneOf($tokens, $i, ['(', ']', ',']));
      if ($id === '(') {
        yield $this->parseArgsGetRawAttribute($tokens, $i, $qcn);
        assert($tokens[$i] === ')');
        assert(ParserUtil::expect($tokens, $i, ')'));
        ++$i;
        $id = ParserUtil::skipFillerWs($tokens, $i);
      }
      else {
        yield new RawAttribute_NoArgs($qcn);
      }
      if ($id === ']') {
        $pos = $i;
        return;
      }
      if ($id !== ',') {
        throw SyntaxException::expectedButFound($tokens, $i, "']' or ','");
      }
    }
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position of '('.
   *   After: Position of ')'.
   * @param class-string $qcn
   *
   * @return \Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface
   *   Raw attribute.
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  private function parseArgsGetRawAttribute(array $tokens, int &$pos, string $qcn): RawAttributeInterface {
    assert(ParserUtil::expect($tokens, $pos, '('));
    $i = $pos;
    $php = '';
    $namedPart = FALSE;
    while (TRUE) {
      assert(ParserUtil::expectOneOf($tokens, $i, ['(', ',']));
      ++$i;
      $id = ParserUtil::skipFillerWs($tokens, $i);
      $key = NULL;
      // Parse optional key for named parameter syntax.
      if ($id === T_STRING) {
        $iNamed = $i + 1;
        $idNamed = ParserUtil::skipFillerWs($tokens, $iNamed);
        if ($idNamed === ':') {
          $key = $tokens[$i][1];
          $i = $iNamed + 1;
          $id = ParserUtil::skipFillerWs($tokens, $i);
        }
      }
      if ($id !== ')' && $id !== ',') {
        $iValueBegin = $i;
        $id = ParserUtil::skipValueExpression($tokens, $i);
        $valuePhp = ParserUtil::concatTokens($tokens, $iValueBegin, $i);
        if ($key !== NULL) {
          $php .= '  ' . \var_export($key, TRUE) . ' => ' . $valuePhp . ",\n";
          $namedPart = TRUE;
        }
        elseif ($namedPart) {
          throw SyntaxException::fromTokenPos($tokens, $i, 'Cannot use positional argument after named argument');
        }
        else {
          assert(!preg_match('@^\w+\:[^\:]@', $valuePhp));
          $php .= '  ' . $valuePhp . ",\n";
        }
      }
      elseif ($key !== NULL) {
        throw SyntaxException::unexpected($tokens, $i, 'after argument name');
      }
      if ($id === ')') {
        $pos = $i;
        break;
      }
      assert($id === ',');
    }

    if ($php === '') {
      assert($tokens[$pos] === ')');
      return new RawAttribute_NoArgs($qcn);
    }

    $php = "[\n" . $php . ']';

    $php = $this->buildEval($php);

    assert($tokens[$pos] === ')');
    return new RawAttribute_Eval($qcn, $php);
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position of first T_STRING.
   *   After: Position after last T_STRING.
   *
   * @return class-string
   *   Resolved QCN.
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  private function parseClassRef(array $tokens, int &$pos): string {
    if ($tokens[$pos][0] === T_NS_SEPARATOR) {
      $i = $pos + 1;
      if ($tokens[$i][0] !== T_STRING) {
        throw SyntaxException::expectedButFound($tokens, $pos, 'T_STRING');
      }
      $qcn = $tokens[$i][1];
      ++$i;
    }
    elseif ($tokens[$pos][0] === T_STRING) {
      $name = $tokens[$pos][1];
      $qcn = $this->imports[$name] ?? $this->terminatedNamespace . $name;
      $i = $pos + 1;
    }
    else {
      throw SyntaxException::expectedButFound($tokens, $pos, 'QCN or FQCN');
    }
    if ($tokens[$i][0] !== T_NS_SEPARATOR) {
      if (\strtolower($qcn) === 'self') {
        if ($this->class === NULL) {
          throw SyntaxException::fromTokenPos($tokens, $i, 'self outside of class');
        }
        return $this->class;
      }
      $pos = $i;
      /** @var class-string $qcn */
      return $qcn;
    }
    while (TRUE) {
      ++$i;
      if ($tokens[$i][0] !== T_STRING) {
        throw SyntaxException::expectedButFound($tokens, $i, 'T_STRING');
      }
      $qcn .= '\\' . $tokens[$i][1];
      ++$i;
      if ($tokens[$i][0] !== T_NS_SEPARATOR) {
        $pos = $i;
        /** @var class-string $qcn */
        return $qcn;
      }
    }
  }

  /**
   * @return string
   */
  private function buildFileHead(): string {
    $php = '';
    if ($this->terminatedNamespace !== '') {
      $namespace = \substr($this->terminatedNamespace, 0, -1);
      $php .= "namespace $namespace;\n";
    }
    foreach ($this->imports as $alias => $qcn) {
      // Optimize for the more common case where the alias has no space.
      if (FALSE !== $spacepos = \strpos($alias, ' ')) {
        $type = \substr($alias, 0, $spacepos);
        $alias = \substr($alias, $spacepos + 1);
        $php .= "use $type $qcn as $alias;\n";
      }
      else {
        $php .= "use $qcn as $alias;\n";
      }
    }
    return $php;
  }

  /**
   * @param string $phpExpression
   *
   * @return string
   */
  private function buildEval(string $phpExpression): string {

    $returnStmt = "return $phpExpression;";

    if ($this->class !== NULL) {
      $returnStmt = \str_replace("\n", "\n  ", $returnStmt);
      $returnStmt = <<<EOT
return call_user_func((function() {
  $returnStmt
})->bindTo(null, \\$this->class::class));
EOT;
    }

    $head = $this->buildFileHead();
    return "$head\n$returnStmt";
  }

}
