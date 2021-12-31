<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\AttributeCommentParser;

use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\Exception\SyntaxException;
use Donquixote\QuickAttributes\Exception\UnsupportedSyntaxException;
use Donquixote\QuickAttributes\RawAttribute\RawAttribute_Fixed;
use Donquixote\QuickAttributes\RawAttribute\RawAttribute_NoArgs;
use Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface;
use Donquixote\QuickAttributes\Util\ParserAssertUtil;
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
    \assert($namespace !== '');
    \assert($namespace === NULL || $namespace[0] !== '\\');
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
    $tokens = $this->tokenize($comment);
    \assert(\end($tokens) === '#');
    \assert(ParserAssertUtil::expect($tokens, 0, ParserUtil::T_ATTRIBUTE));
    $rawAttributes = [];
    $i = 0;
    while (TRUE) {
      \assert(ParserAssertUtil::expect($tokens, $i, ParserUtil::T_ATTRIBUTE));
      foreach ($this->parseAttributes($tokens, $i) as $rawAttribute) {
        $rawAttributes[] = $rawAttribute;
      }
      \assert(ParserAssertUtil::expect($tokens, $i, ']'));
      ++$i;
      $id = ParserUtil::skipFillerWs($tokens, $i);
      if ($id === '#') {
        // EOF reached.
        return $rawAttributes;
      }
      if ($id === ParserUtil::T_ATTRIBUTE) {
        continue;
      }
      throw UnsupportedSyntaxException::fromTokenPos($tokens, $i, 'Cannot have regular code after an attribute in the same line.');
    }
  }  // @codeCoverageIgnore

  /**
   * @param string $comment
   *   Single-line comment starting with '#[', ending with newline.
   *
   * @return list<string|array{int, string, int}>
   *   Tokens starting with T_ATTRIBUTE, terminated with `#`.
   */
  private function tokenize(string $comment): array {
    if (\substr($comment, 0, 2) !== '#[') {
      throw new \InvalidArgumentException('Comment must begin with #[.');
    }
    if (\strpos($comment, "\n") !== \strlen($comment) - 1) {
      throw new \InvalidArgumentException('Comment must be single-line and end with line break.' . \var_export($comment, TRUE));
    }
    $php = '<?php ' . \substr($comment, 2);
    /** @var list<string|array{int, string, int}> $tokens */
    $tokens = \token_get_all($php);
    $tokens[0] = [ParserUtil::T_ATTRIBUTE, '#[', 0];
    /**
     * Tell psalm that $tokens is still a list, after setting $tokens[0].
     *
     * @var list<string|array{int, string, int}> $tokens
     */
    while (TRUE) {
      $tkLast = \end($tokens);
      if ($tkLast[0] !== \T_COMMENT) {
        break;
      }
      // At this point it is known that $tkLast is an array, not a string.
      /** @var array{int, string, int} $tkLast */
      $comment = $tkLast[1];
      if ($comment[1] !== '[') {
        // That's a regular comment, not an attribute comment.
        break;
      }
      $php = '<?php ' . \substr($comment, 2);
      /** @var list<string|array{int, string, int}> $moreTokens */
      $moreTokens = \token_get_all($php);
      $moreTokens[0] = [ParserUtil::T_ATTRIBUTE, '#[', 0];
      \array_pop($tokens);
      $tokens = [
        ...$tokens,
        ...$moreTokens,
      ];
    }

    $tokens[] = '#';
    return $tokens;
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position of '#[' / T_ATTRIBUTE.
   *   After: Position of ']'.
   *
   * @return iterable<int, RawAttributeInterface>
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  private function parseAttributes(array $tokens, int &$pos): iterable {
    \assert(ParserAssertUtil::expect($tokens, $pos, ParserUtil::T_ATTRIBUTE));
    $i = $pos;
    while (TRUE) {
      \assert(ParserAssertUtil::expectOneOf($tokens, $i, [ParserUtil::T_ATTRIBUTE, ',']));
      ++$i;
      ParserUtil::skipFillerWs($tokens, $i);
      $iBkp0 = $i;
      $qcn = $this->parseAttributeName($tokens, $i);
      \assert($i > $iBkp0);
      $id = ParserUtil::skipFillerWs($tokens, $i);
      if ($id === '(') {
        yield $this->parseArgsGetRawAttribute($tokens, $i, $qcn);
        \assert($tokens[$i] === ')');
        \assert(ParserAssertUtil::expect($tokens, $i, ')'));
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
  }  // @codeCoverageIgnore

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
    \assert(ParserAssertUtil::expect($tokens, $pos, '('));
    $i = $pos;
    $php = '';
    $namedPart = FALSE;
    while (TRUE) {
      \assert(ParserAssertUtil::expectOneOf($tokens, $i, ['(', ',']));
      ++$i;
      $id = ParserUtil::skipFillerWs($tokens, $i);
      $key = NULL;
      if ($id === ')') {
        // Empty arg list, or trailing comma after last arg.
        $pos = $i;
        break;
      }
      if ($id === \T_STRING) {
        // This could be a named arg, OR a part of a value expression.
        $iNamed = $i + 1;
        $idNamed = ParserUtil::skipFillerWs($tokens, $iNamed);
        if ($idNamed === ':') {
          // This is indeed a named argument.
          $key = $tokens[$i][1];
          $i = $iNamed + 1;
          ParserUtil::skipFillerWs($tokens, $i);
        }
      }
      // Parse a value expression.
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
        \assert(!\preg_match('@^\w+\:[^\:]@', $valuePhp));
        $php .= '  ' . $valuePhp . ",  // key => value\n";
      }
      if ($id === ')') {
        $pos = $i;
        break;
      }
      \assert(ParserAssertUtil::expect($tokens, $i, ','));
    }

    \assert($tokens[$pos] === ')');

    if ($php === '') {
      return new RawAttribute_NoArgs($qcn);
    }

    $php = "[\n" . $php . ']';

    try {
      \set_error_handler([self::class, 'error']);
      /** @var array $args */
      $args = self::doEval($php);
    }
    catch (ParserException $e) {
      throw $e;
    }
    catch (\Throwable $e) {
      throw new SyntaxException($e->getMessage(), 0, $e);
    }
    finally {
      \restore_error_handler();
    }

    return new RawAttribute_Fixed($qcn, $args);
  }

  /**
   * @param int $code
   * @param string $message
   * @param string $file
   * @param int $line
   *
   * @return bool
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  public static function error(int $code, string $message, string $file = 'unknown', int $line = -1): bool {
    throw new SyntaxException('Eval: ' . $message);
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
  private function parseAttributeName(array $tokens, int &$pos): string {
    if ($tokens[$pos][0] === \T_NS_SEPARATOR) {
      $i = $pos + 1;
      if ($tokens[$i][0] !== \T_STRING) {
        throw SyntaxException::expectedButFound($tokens, $pos, 'T_STRING');
      }
      $qcn = $tokens[$i][1];
      ++$i;
    }
    elseif ($tokens[$pos][0] === \T_STRING) {
      $name = $tokens[$pos][1];
      $qcn = $this->imports[$name] ?? $this->terminatedNamespace . $name;
      $i = $pos + 1;
    }
    else {
      throw SyntaxException::expectedButFound($tokens, $pos, 'QCN or FQCN');
    }
    if ($tokens[$i][0] !== \T_NS_SEPARATOR) {
      $pos = $i;
      /** @var class-string $qcn */
      return $qcn;
    }
    // Parse remaining part of the QCN.
    while (TRUE) {
      ++$i;
      if ($tokens[$i][0] !== \T_STRING) {
        throw SyntaxException::expectedButFound($tokens, $i, 'T_STRING');
      }
      $qcn .= '\\' . $tokens[$i][1];
      ++$i;
      if ($tokens[$i][0] !== \T_NS_SEPARATOR) {
        $pos = $i;
        /** @var class-string $qcn */
        return $qcn;
      }
    }
  }  // @codeCoverageIgnore

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: First token of value expression.
   *   After: Position of ')' or ',' or '}' or ']'.
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
          case '.':
            $php .= $token;
            break;

          case '(':
            ++$i;
            $php .= '(' . $this->parseValueExpression($tokens, $i) . ')';
            \assert(ParserAssertUtil::expect($tokens, $i, ')'));
            break;

          case '[':
            $php .= $this->parseArray($tokens, $i, ']');
            \assert($tokens[$i] === ']');
            break;

          case ')':
          case ']':
          case ',':
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
            // Attribute-like comments are already cleared out by tokenizer.
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

    if ($pos === $i) {
      throw SyntaxException::expectedButFound($tokens, $i, 'value expression');
    }
    \assert($php !== '');
    \assert(ParserAssertUtil::expectOneOf($tokens, $i, [',', ';', ')', '}', ']', \T_DOUBLE_ARROW]));
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
    \assert(ParserAssertUtil::expectOneOf($tokens, $pos, ['(', '[']));
    $i = $pos;
    $php = '';
    while (TRUE) {
      \assert(ParserAssertUtil::expectOneOf($tokens, $i, ['(', '[', ',']));
      ++$i;
      $id = ParserUtil::skipFillerWs($tokens, $i);
      if ($id === $endchar) {
        $pos = $i;
        break;
      }
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
   *   Before: Position of first T_STRING or T_NS_SEPARATOR.
   *   After: Position after last T_STRING or T_CLASS.
   *
   * @return string
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  private function parseConstRef(array $tokens, int &$pos): string {
    if ($tokens[$pos][0] === \T_NS_SEPARATOR) {
      $i = $pos + 1;
      if ($tokens[$i][0] !== \T_STRING) {
        throw SyntaxException::expectedButFound($tokens, $i, 'T_STRING');
      }
      $first = NULL;
      $fqn = $tokens[$i][1];
      ++$i;
    }
    else {
      \assert(ParserAssertUtil::expect($tokens, $pos, \T_STRING));
      $first = $tokens[$pos][1];
      $fqn = '';
      $i = $pos + 1;
    }
    while ($tokens[$i][0] === \T_NS_SEPARATOR) {
      ++$i;
      if ($tokens[$i][0] !== \T_STRING) {
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
    if ($id !== \T_DOUBLE_COLON) {
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
    if ($id === \T_CLASS) {
      $fqn .= '::class';
      $pos = $i + 1;
      return $fqn;
    }
    if ($id !== \T_STRING) {
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
