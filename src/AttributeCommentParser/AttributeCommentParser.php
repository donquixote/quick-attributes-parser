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
use Donquixote\QuickAttributes\Util\VersionDependentTokens;

class AttributeCommentParser implements AttributeCommentParserInterface {

  private string $terminatedNamespace = '';

  /**
   * @var array<string, string>
   */
  private array $imports = [];

  /**
   * @var class-string|null
   */
  private ?string $class = null;

  /**
   * @param string|null $namespace
   * @param array<string, string> $imports
   * @param class-string|null $class
   *
   * @return static
   */
  public function withContext(?string $namespace, array $imports, ?string $class): self {
    \assert($namespace !== '');
    \assert($namespace === null || $namespace[0] !== '\\');
    $clone = clone $this;
    $clone->terminatedNamespace = ($namespace === null) ? '' : ($namespace . '\\');
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
    \assert(ParserAssertUtil::expect($tokens, 0, VersionDependentTokens::T_ATTRIBUTE));
    $rawAttributes = [];
    $i = 0;
    while (true) {
      \assert(ParserAssertUtil::expect($tokens, $i, VersionDependentTokens::T_ATTRIBUTE));
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
      if ($id === VersionDependentTokens::T_ATTRIBUTE) {
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
      throw new \InvalidArgumentException('Comment must be single-line and end with line break.' . \var_export($comment, true));
    }
    $php = '<?php ' . \substr($comment, 2);
    /** @var list<string|array{int, string, int}> $tokens */
    $tokens = \token_get_all($php);
    $tokens[0] = [VersionDependentTokens::T_ATTRIBUTE, '#[', 0];
    /**
     * Tell psalm that $tokens is still a list, after setting $tokens[0].
     *
     * @var list<string|array{int, string, int}> $tokens
     */
    while (true) {
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
      $moreTokens[0] = [VersionDependentTokens::T_ATTRIBUTE, '#[', 0];
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
    \assert(ParserAssertUtil::expect($tokens, $pos, VersionDependentTokens::T_ATTRIBUTE));
    while (true) {
      \assert(ParserAssertUtil::expectOneOf($tokens, $pos, [VersionDependentTokens::T_ATTRIBUTE, ',']));
      ++$pos;
      ParserUtil::skipFillerWs($tokens, $pos);
      $iBkp0 = $pos;
      if (\PHP_VERSION_ID < 80000) {
        $qcn = $this->parseAttributeNamePhp7($tokens, $pos);
      }
      else {
        $qcn = $this->parseAttributeNamePhp8($tokens, $pos);
      }
      \assert($pos > $iBkp0);
      $id = ParserUtil::skipFillerWs($tokens, $pos);
      if ($id === '(') {
        yield $this->parseArgsGetRawAttribute($tokens, $pos, $qcn);
        \assert($tokens[$pos] === ')');
        \assert(ParserAssertUtil::expect($tokens, $pos, ')'));
        ++$pos;
        $id = ParserUtil::skipFillerWs($tokens, $pos);
      }
      else {
        yield new RawAttribute_NoArgs($qcn);
      }
      if ($id === ']') {
        return;
      }
      if ($id !== ',') {
        throw SyntaxException::expectedButFound($tokens, $pos, "']' or ','");
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
    $php = '';
    $namedPart = false;
    while (true) {
      \assert(ParserAssertUtil::expectOneOf($tokens, $pos, ['(', ',']));
      ++$pos;
      $id = ParserUtil::skipFillerWs($tokens, $pos);
      $key = null;
      if ($id === ')') {
        // Empty arg list, or trailing comma after last arg.
        break;
      }
      if ($id === \T_STRING) {
        // This could be a named arg, OR a part of a value expression.
        $iNamed = $pos + 1;
        $idNamed = ParserUtil::skipFillerWs($tokens, $iNamed);
        if ($idNamed === ':') {
          // This is indeed a named argument.
          $key = $tokens[$pos][1];
          $pos = $iNamed + 1;
          ParserUtil::skipFillerWs($tokens, $pos);
        }
      }
      // Parse a value expression.
      $valuePhp = $this->parseValueExpression($tokens, $pos);
      \assert($valuePhp !== '');
      $id = $tokens[$pos][0];
      if ($key !== null) {
        $php .= '  ' . \var_export($key, true) . ' => ' . $valuePhp . ",  // value\n";
        $namedPart = true;
      }
      elseif ($namedPart) {
        throw SyntaxException::fromTokenPos($tokens, $pos, 'Cannot use positional argument after named argument');
      }
      else {
        \assert(!\preg_match('@^\w+\:[^\:]@', $valuePhp));
        $php .= '  ' . $valuePhp . ",  // key => value\n";
      }
      if ($id === ')') {
        break;
      }
      \assert(ParserAssertUtil::expect($tokens, $pos, ','));
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
   * Attribute name, as QCN or FQCN.
   *
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position of first T_STRING or T_NAMESPACE_SEPARATOR.
   *   After: Position after last T_STRING.
   *
   * @return class-string
   *   Resolved QCN.
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  private function parseAttributeNamePhp7(array $tokens, int &$pos): string {
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
   * Attribute name, as QCN or FQCN.
   *
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before (good): Position of first T_STRING, T_NAME_QUALIFIED or
   *     T_NAME_FULLY_QUALIFIED.
   *   Before (bad): Anything else leads to a SyntaxException.
   *   After: Position after last T_STRING.
   *
   * @return class-string
   *   Resolved QCN.
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  private function parseAttributeNamePhp8(array $tokens, int &$pos): string {
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
    $iStart = $pos;
    $php = '';
    while (true) {
      $token = $tokens[$pos];
      if (\is_string($token)) {
        switch ($token) {
          case '-':
          case '+':
          case '*':
          case '/':
          case '.':
          case '&':
          case '|':
            $php .= $token;
            break;

          case '(':
            ++$pos;
            $php .= '(' . $this->parseValueExpression($tokens, $pos) . ')';
            \assert(ParserAssertUtil::expect($tokens, $pos, ')'));
            break;

          case '[':
            $php .= $this->parseArray($tokens, $pos, ']');
            \assert($tokens[$pos] === ']');
            break;

          case ')':
          case ']':
          case ',':
            // End of value expression.
            break 2;

          default:
            throw SyntaxException::unexpected($tokens, $pos, 'in const value expression');
        }
      }
      else {
        switch ($token[0]) {
          case \T_ARRAY:
            ++$pos;
            ParserUtil::skipFillerWsExpectChar($tokens, $pos, '(');
            $php .= $this->parseArray($tokens, $pos, ')');
            break;

          case \T_LNUMBER:
          case \T_CONSTANT_ENCAPSED_STRING:
            $php .= $token[1];
            break;

          case \T_COMMENT:
            // Attribute-like comments are already cleared out by tokenizer.
            $php .= ' ';
            break;

          case VersionDependentTokens::T_NAME_FULLY_QUALIFIED:
          case VersionDependentTokens::T_NAME_QUALIFIED:
          case VersionDependentTokens::T_STRING_8:
            $php .= $this->parseConstRefPhp8($tokens, $pos);
            // Continue, but don't increment $i.
            continue 2;

          case VersionDependentTokens::T_NS_SEPARATOR_7:
          case VersionDependentTokens::T_STRING_7:
            $php .= $this->parseConstRefPhp7($tokens, $pos);
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
            throw SyntaxException::unexpected($tokens, $pos, 'in const value expression');
        }
      }
      ++$pos;
    }

    if ($pos === $iStart) {
      throw SyntaxException::expectedButFound($tokens, $pos, 'value expression');
    }
    \assert($php !== '');
    \assert(ParserAssertUtil::expectOneOf($tokens, $pos, [',', ';', ')', '}', ']', \T_DOUBLE_ARROW]));
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
    $php = '';
    while (true) {
      \assert(ParserAssertUtil::expectOneOf($tokens, $pos, ['(', '[', ',']));
      ++$pos;
      $id = ParserUtil::skipFillerWs($tokens, $pos);
      if ($id === $endchar) {
        break;
      }
      $php .= '  ' . $this->parseValueExpression($tokens, $pos);
      $id = $tokens[$pos][0];
      if ($id === \T_DOUBLE_ARROW) {
        ++$pos;
        ParserUtil::skipFillerWs($tokens, $pos);
        $php .= ' => ' . $this->parseValueExpression($tokens, $pos);
        $id = $tokens[$pos][0];
      }
      $php .= ",\n";
      if ($id === $endchar) {
        break;
      }
      if ($id !== ',') {
        throw SyntaxException::expectedButFound($tokens, $pos, "$endchar or ,");
      }
    }

    \assert($tokens[$pos] === $endchar);

    if ($php === '') {
      return '[]';
    }

    return "[\n" . $php . ']';
  }

  /**
   * Expression referencing a global or class constant or *::class.
   *
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position of first T_STRING or T_NS_SEPARATOR.
   *   After: Position after last T_STRING or T_CLASS.
   *
   * @return string
   *   Fully-qualified name, with possible '::class' or '::$name' appended.
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  private function parseConstRefPhp7(array $tokens, int &$pos): string {
    \assert(\PHP_VERSION_ID < 80000);
    if ($tokens[$pos][0] === \T_NS_SEPARATOR) {
      ++$pos;
      if ($tokens[$pos][0] !== \T_STRING) {
        throw SyntaxException::expectedButFound($tokens, $pos, 'T_STRING');
      }
      $first = null;
      $fqn = $tokens[$pos][1];
    }
    else {
      \assert(ParserAssertUtil::expect($tokens, $pos, \T_STRING));
      $first = $tokens[$pos][1];
      $fqn = '';
    }
    ++$pos;
    while ($tokens[$pos][0] === \T_NS_SEPARATOR) {
      ++$pos;
      if ($tokens[$pos][0] !== \T_STRING) {
        throw SyntaxException::expectedButFound($tokens, $pos, 'T_STRING');
      }
      $fqn .= '\\' . $tokens[$pos][1];
      ++$pos;
    }
    $iAfterName = $pos;
    $id = ParserUtil::skipFillerWs($tokens, $pos);
    if ($id === '(') {
      throw SyntaxException::fromTokenPos($tokens, $pos, 'Function call not allowed in constant expression.');
    }
    if ($id !== \T_DOUBLE_COLON) {
      // Fqn refers to a global constant.
      if ($first !== null) {
        // Allow PHP to do a fallback lookup for the constant, if not imported.
        // A namespace declaration must be prepended to eval'd code.
        $qn = $this->imports["const $first"] ?? null;
        if ($qn !== null) {
          $fqn = '\\' . $qn . $fqn;
        }
        else {
          $fqn = \vsprintf('(defined(%s) ? %s : %s)', [
            \var_export($this->terminatedNamespace . $first . $fqn, true),
            '\\' . $this->terminatedNamespace . $first . $fqn,
            '\\' . $first . $fqn,
          ]);
        }
      }
      $pos = $iAfterName;
      return $fqn;
    }
    // Fqn refers to a class.
    if ($first !== null) {
      if ($fqn === '' && \strtolower($first) === 'self') {
        if ($this->class === null) {
          throw SyntaxException::unexpected($tokens, $pos, 'outside of class context');
        }
        $fqn = '\\' . $this->class;
      }
      else {
        $fqn = '\\' . ($this->imports[$first] ?? $this->terminatedNamespace . $first) . $fqn;
      }
    }
    ++$pos;
    $id = ParserUtil::skipFillerWs($tokens, $pos);
    if ($id === \T_CLASS) {
      $fqn .= '::class';
      ++$pos;
      return $fqn;
    }
    if ($id !== \T_STRING) {
      throw SyntaxException::expectedButFound($tokens, $pos, 'T_STRING or T_CLASS');
    }
    $fqn .= '::' . $tokens[$pos][1];
    ++$pos;
    $forwardPos = $pos;
    $id = ParserUtil::skipFillerWs($tokens, $forwardPos);
    if ($id === '(') {
      throw SyntaxException::fromTokenPos($tokens, $forwardPos, 'Method call not allowed in constant expression.');
    }
    // Fqn refers to a class constant.
    return $fqn;
  }

  /**
   * Expression referencing a global or class constant or *::class.
   *
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position of first T_STRING, T_NAME_QUALIFIED or
   *     T_NAME_FULLY_QUALIFIED.
   *   After: Position of next non-whitespace token after the const reference.
   *
   * @return string
   *   Fully-qualified name, with possible '::class' or '::$name' appended.
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  private function parseConstRefPhp8(array $tokens, int &$pos): string {
    \assert(\PHP_VERSION_ID >= 80000);
    $posFirst = $pos;
    $tkFirst = $tokens[$posFirst];
    $idFirst = $tkFirst[0];
    ++$pos;
    $idNext = ParserUtil::skipFillerWs($tokens, $pos);
    if ($idNext === '(') {
      // Function call.
      throw SyntaxException::fromTokenPos($tokens, $pos, 'Function call not allowed in constant expression.');
    }
    if ($idFirst === \T_NAME_FULLY_QUALIFIED) {
      $fqn = $tkFirst[1];
    }
    elseif ($idFirst === \T_NAME_QUALIFIED) {
      $qn = $tkFirst[1];
      $nspos = \strpos($qn, '\\');
      \assert($nspos !== 0 && $nspos !== false);
      $alias = \substr($qn, 0, $nspos);
      if (!isset($this->imports[$alias])) {
        $fqn = '\\' . $this->terminatedNamespace . $qn;
      }
      else {
        $fqn = '\\' . ($this->imports[$alias] . \substr($qn, $nspos));
      }
    }
    else {
      \assert($idFirst === \T_STRING);
      $alias = $tkFirst[1];
      if ($idNext !== \T_DOUBLE_COLON) {
        // Global constant.
        return '\\' . ($this->imports["const $alias"] ?? $this->terminatedNamespace . $alias);
      }
      // Class constant or ::class expression.
      if (\strtolower($alias) === 'self') {
        if ($this->class === null) {
          throw SyntaxException::unexpected($tokens, $posFirst, 'outside of class context');
        }
        $fqn = '\\' .$this->class;
      }
      else {
        $fqn = '\\' . ($this->imports[$alias] ?? $this->terminatedNamespace . $alias);
      }
    }
    if ($idNext !== \T_DOUBLE_COLON) {
      // Global constant.
      return $fqn;
    }
    // Fqn refers to a class.
    ++$pos;
    $id = ParserUtil::skipFillerWs($tokens, $pos);
    if ($id === \T_CLASS) {
      $expr = $fqn . '::class';
    }
    elseif ($id === \T_STRING) {
      $expr = $fqn . '::' . $tokens[$pos][1];
    }
    else {
      throw SyntaxException::expectedButFound($tokens, $pos, 'T_STRING or T_CLASS');
    }
    ++$pos;
    $id = ParserUtil::skipFillerWs($tokens, $pos);
    if ($id === '(') {
      throw SyntaxException::fromTokenPos($tokens, $pos, 'Method call not allowed in constant expression.');
    }
    return $expr;
  }

}
