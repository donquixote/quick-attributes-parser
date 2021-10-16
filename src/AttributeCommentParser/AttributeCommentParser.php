<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\AttributeCommentParser;

use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\Exception\SyntaxException;
use Donquixote\QuickAttributes\RawAttribute\RawAttribute_Fixed;
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
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
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
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
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
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
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
        $valuePhp = $this->parseValueExpression($tokens, $i);
        \assert($valuePhp !== '');
        $id = $tokens[$i][0];
        if ($key !== NULL) {
          $php .= '  ' . \var_export($key, TRUE) . ' => ' . $valuePhp . ",  // value\n";
          $namedPart = TRUE;
        }
        elseif ($namedPart) {
          throw SyntaxException::fromTokenPos($tokens, $i, 'Cannot use positional argument after named argument');
        }
        else {
          assert(!preg_match('@^\w+\:[^\:]@', $valuePhp));
          $php .= '  ' . $valuePhp . ",  // key => value\n";
        }
      }
      elseif ($key !== NULL) {
        throw SyntaxException::unexpected($tokens, $i, 'after argument name');
      }
      if ($id === ')') {
        $pos = $i;
        break;
      }
      \assert(ParserUtil::expect($tokens, $i, ','));
    }

    assert($tokens[$pos] === ')');

    if ($php === '') {
      return new RawAttribute_NoArgs($qcn);
    }

    $php = "[\n" . $php . ']';

    try {
      /** @var array $args */
      $args = self::doEval($php);
    }
    catch (ParserException $e) {
      throw $e;
    }
    catch (\Throwable $e) {
      throw new SyntaxException($e->getMessage(), 0, $e);
    }

    return new RawAttribute_Fixed($qcn, $args);
  }

  /**
   * @param string $php
   *
   * @return mixed
   *
   * @throws \Throwable
   */
  private static function doEval(string $php) {
    return eval("return $php;");
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
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *
   * @return string
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  private function parseValueExpression(array $tokens, int &$pos): string {
    $i = $pos;
    $php = '';
    while (TRUE) {
      $token = $tokens[$i];
      if (\is_string($token)) {
        switch ($token) {
          case '-':
          case '+':
          case '*':
          case '/':
            $php .= $token;
            break;

          case '(':
          case '{':
          case '[':
            $php .= $this->parseArray($tokens, $i, ']');
            \assert($tokens[$i] === ']');
            break;

          case ')':
          case '}':
          case ']':
          case ',':
          case ';':
            // End of value expression.
            break 2;

          default:
            throw SyntaxException::unexpected($tokens, $i, 'in const value expression');
        }
      }
      else {
        switch ($token[0]) {
          case \T_ARRAY:
            ++$i;
            ParserUtil::skipFillerWsExpectChar($tokens, $i, '(');
            $php .= $this->parseArray($tokens, $i, ')');
            break;

          case \T_LNUMBER:
          case \T_CONSTANT_ENCAPSED_STRING:
            $php .= $token[1];
            break;

          case \T_COMMENT:
            if (PHP_VERSION_ID < 80000 && $tokens[$i][1][1] === '[') {
              // Found an attribute.
              throw SyntaxException::unexpected($tokens, $i, 'in value expression');
            }
            $php .= ' ';
            break;

          case \T_NS_SEPARATOR:
          case \T_STRING:
            $php .= $this->parseConstRef($tokens, $i);
            // Continue, but don't increment $i.
            continue 2;

          case \T_DOC_COMMENT:
          case \T_WHITESPACE:
            $php .= ' ';
            break;

          case \T_DOUBLE_ARROW:
            // End of value expression.
            break 2;

          default:
            throw SyntaxException::unexpected($tokens, $i, 'in const value expression');
        }
      }
      ++$i;
    }

    \assert($php !== '');
    \assert(ParserUtil::expectOneOf($tokens, $i, [',', ';', ')', '}', ']', \T_DOUBLE_ARROW]));
    $pos = $i;
    return $php;
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position of '(' or '['.
   *   After: Position of ')' or ']'.
   * @param string $endchar
   *   Expected end character. One of ')' or ']'.
   *
   * @return string
   *   New PHP expression.
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  private function parseArray(array $tokens, int &$pos, string $endchar): string {
    \assert(ParserUtil::expectOneOf($tokens, $pos, ['(', '[']));
    $i = $pos;
    $php = '';
    while (TRUE) {
      \assert(ParserUtil::expectOneOf($tokens, $i, ['(', '[', ',']));
      ++$i;
      ParserUtil::skipFillerWs($tokens, $i);
      $php .= '  ' . $this->parseValueExpression($tokens, $i);
      $id = $tokens[$i][0];
      if ($id === \T_DOUBLE_ARROW) {
        ++$i;
        ParserUtil::skipFillerWs($tokens, $i);
        $php .= ' => ' . $this->parseValueExpression($tokens, $i);
        $id = $tokens[$i][0];
      }
      $php .= ",\n";
      if ($id === $endchar) {
        $pos = $i;
        break;
      }
      if ($id !== ',') {
        throw SyntaxException::expectedButFound($tokens, $i, "$endchar or ,");
      }
    }

    \assert($tokens[$pos] === $endchar);

    if ($php === '') {
      return '[]';
    }

    return "[\n" . $php . ']';
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position of first T_STRING.
   *   After: Position after last T_STRING.
   *
   * @return string
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  private function parseConstRef(array $tokens, int &$pos): string {
    if ($tokens[$pos][0] === T_NS_SEPARATOR) {
      $i = $pos + 1;
      if ($tokens[$i][0] !== T_STRING) {
        throw SyntaxException::expectedButFound($tokens, $pos, 'T_STRING');
      }
      $first = NULL;
      $fqn = $tokens[$i][1];
      ++$i;
    }
    elseif ($tokens[$pos][0] === T_STRING) {
      $first = $tokens[$pos][1];
      $fqn = '';
      $i = $pos + 1;
    }
    else {
      throw SyntaxException::expectedButFound($tokens, $pos, 'QCN or FQCN');
    }
    while ($tokens[$i][0] === T_NS_SEPARATOR) {
      ++$i;
      if ($tokens[$i][0] !== T_STRING) {
        throw SyntaxException::expectedButFound($tokens, $i, 'T_STRING');
      }
      $fqn .= '\\' . $tokens[$i][1];
      ++$i;
    }
    $iAfterName = $i;
    $id = ParserUtil::skipFillerWs($tokens, $i);
    if ($id === '(') {
      throw SyntaxException::fromTokenPos($tokens, $i, 'Function call not allowed in constant expression.');
    }
    if ($id !== T_DOUBLE_COLON) {
      // Fqn refers to a global constant.
      if ($first !== NULL) {
        // Allow PHP to do a fallback lookup for the constant, if not imported.
        // A namespace declaration must be prepended to eval'd code.
        $qn = $this->imports["const $first"] ?? null;
        if ($qn !== NULL) {
          $fqn = '\\' . $qn . $fqn;
        }
        else {
          $fqn = \vsprintf('(defined(%s) ? %s : %s)', [
            \var_export($this->terminatedNamespace . $first . $fqn, TRUE),
            '\\' . $this->terminatedNamespace . $first . $fqn,
            '\\' . $first . $fqn,
          ]);
        }
      }
      $pos = $iAfterName;
      return $fqn;
    }
    // Fqn refers to a class.
    if ($first !== NULL) {
      if ($fqn === '' && \strtolower($first) === 'self') {
        if ($this->class === NULL) {
          throw SyntaxException::unexpected($tokens, $i, 'outside of class context');
        }
        $fqn = '\\' . $this->class;
      }
      else {
        $fqn = '\\' . ($this->imports[$first] ?? $this->terminatedNamespace . $first) . $fqn;
      }
    }
    ++$i;
    $id = ParserUtil::skipFillerWs($tokens, $i);
    if ($id === T_CLASS) {
      $fqn .= '::class';
      $pos = $i + 1;
      return $fqn;
    }
    if ($id !== T_STRING) {
      throw SyntaxException::expectedButFound($tokens, $i, 'T_STRING or T_CLASS');
    }
    $fqn .= '::' . $tokens[$i][1];
    ++$i;
    $pos = $i;
    $id = ParserUtil::skipFillerWs($tokens, $i);
    if ($id === '(') {
      throw SyntaxException::fromTokenPos($tokens, $i, 'Method call not allowed in constant expression.');
    }
    // Fqn refers to a class constant.
    return $fqn;
  }

}