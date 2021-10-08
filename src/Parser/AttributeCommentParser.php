<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Parser;

use Donquixote\QuickAttributes\Exception\SyntaxException;
use Donquixote\QuickAttributes\Util\ParserUtil;
use Donquixote\QuickAttributes\Value\RawAttribute;
use Donquixote\QuickAttributes\ValueExpression\ValueExpression_Eval;
use Donquixote\QuickAttributes\ValueExpression\ValueExpressionInterface;

class AttributeCommentParser {

  private string $terminatedNamespace = '';

  private array $imports = [];

  private ?string $class = NULL;

  /**
   * @param string|null $namespace
   * @param array $imports
   * @param string|null $class
   *
   * @return $this
   */
  public function withContext(?string $namespace, array $imports, ?string $class): self {
    $clone = clone $this;
    $clone->terminatedNamespace = ($namespace === NULL) ? '' : $namespace . '\\';
    $clone->imports = $imports;
    $clone->class = $class;
    return $clone;
  }

  /**
   * @param string $comment
   *
   * @return \Iterator<RawAttribute>
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  public function parse(string $comment): \Iterator {
    while (TRUE) {
      yield from $this->doParse($comment, $next);
      if ($next === NULL) {
        return;
      }
      $comment = $next;
    }
  }

  /**
   * @param string $comment
   * @param string|null $next
   *
   * @return \Iterator<RawAttribute>
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  private function doParse(string $comment, ?string &$next): \Iterator {
    if(substr($comment, 0, 2) !== '#[') {
      throw new \InvalidArgumentException('Comment must begin with #[.');
    }
    $php = '<?php [' . substr($comment, 2);
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
      return;
    }
    if ($id !== T_COMMENT) {
      throw SyntaxException::expectedButFound($tokens, $i, 'T_COMMENT or EOF');
    }
    // Another attribute comment reached.
    assert(count($tokens) === $i + 1);
    $next = $tokens[$i][1];
  }

  /**
   * @param array $tokens
   * @param int $pos
   *   Before: Position of '['.
   *   After: Position of ']'.
   *
   * @return \Iterator<int, RawAttribute>
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  private function parseAttributes(array $tokens, int &$pos): \Iterator {
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
        $argsValueExpression = $this->parseArgs($tokens, $i);
        assert($tokens[$i] === ')');
        assert(ParserUtil::expect($tokens, $i, ')'));
        ++$i;
        $id = ParserUtil::skipFillerWs($tokens, $i);
      }
      else {
        $argsValueExpression = NULL;
      }
      yield new RawAttribute($qcn, $argsValueExpression);
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
   * @param array $tokens
   * @param int $pos
   *   Before: Position of '('.
   *   After: Position of ')'.
   *
   * @return \Donquixote\QuickAttributes\ValueExpression\ValueExpressionInterface|null
   *   Value expression for argument values.
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  private function parseArgs(array $tokens, int &$pos): ?ValueExpressionInterface {
    assert(ParserUtil::expect($tokens, $pos, '('));
    $i = $pos;
    $php = '';
    while (TRUE) {
      assert(ParserUtil::expectOneOf($tokens, $i, ['(', ',']));
      ++$i;
      $id = ParserUtil::skipFillerWs($tokens, $i);
      $key = NULL;
      // Parse optional key for named parameter syntax.
      if ($id === T_STRING) {
        $iNamed = $i;
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
          $php .= '  ' . var_export($key, TRUE) . ' => ' . $valuePhp . ",\n";
        }
        else {
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
      return NULL;
    }

    $php = "[\n" . $php . '];';

    $php = $this->buildEval($php);

    assert($tokens[$pos] === ')');
    return new ValueExpression_Eval($php);
  }

  /**
   * @param array $tokens
   * @param int $pos
   *   Before: Position of first T_STRING.
   *   After: Position after last T_STRING.
   *
   * @return string
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
      if (strtolower($qcn) === 'self') {
        if ($this->class === NULL) {
          throw SyntaxException::fromTokenPos($tokens, $i, 'self outside of class');
        }
        return $this->class;
      }
      $pos = $i;
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
      $namespace = substr($this->terminatedNamespace, 0, -1);
      $php .= "namespace $namespace;\n";
    }
    foreach ($this->imports as $alias => $qcn) {
      // Optimize for the more common case where the alias has no space.
      if (FALSE !== $spacepos = strpos($alias, ' ')) {
        $type = substr($alias, 0, $spacepos);
        $alias = substr($alias, $spacepos + 1);
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

    if ($this->class !== NULL) {
      $phpExpression = <<<EOT
call_user_func((function() {
  return $phpExpression;
})->bindTo(null, \\$this->class::class));
EOT;
    }

    $head = $this->buildFileHead();
    return "$head\nreturn $phpExpression";
  }

}
