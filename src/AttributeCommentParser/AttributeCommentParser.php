<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\AttributeCommentParser;

use Donquixote\QuickAttributes\Builder\Arguments\ArgumentsBuilderInterface;
use Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilderInterface;
use Donquixote\QuickAttributes\Builder\Value\ArrayBuilderInterface;
use Donquixote\QuickAttributes\Builder\Value\ValueBuilderInterface;
use Donquixote\QuickAttributes\Exception\SyntaxException;
use Donquixote\QuickAttributes\Exception\UnsupportedSyntaxException;
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
  public function parse(AttributesBuilderInterface $builder, string $comment): void {
    $tokens = $this->tokenize($comment);
    \assert(\end($tokens) === '#');
    \assert(ParserAssertUtil::expect($tokens, 0, VersionDependentTokens::T_ATTRIBUTE));
    $i = 0;
    while (true) {
      \assert(ParserAssertUtil::expect($tokens, $i, VersionDependentTokens::T_ATTRIBUTE));
      $this->parseAttributes($builder, $tokens, $i);
      \assert(ParserAssertUtil::expect($tokens, $i, ']'));
      ++$i;
      $id = ParserUtil::skipFillerWs($tokens, $i);
      if ($id === '#') {
        // EOF reached.
        return;
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
   * @param \Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilderInterface $builder
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position of '#[' / T_ATTRIBUTE.
   *   After: Position of ']'.
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  private function parseAttributes(AttributesBuilderInterface $builder, array $tokens, int &$pos): void {
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
      $args = $builder->addAttribute($qcn);
      $id = ParserUtil::skipFillerWs($tokens, $pos);
      if ($id === '(') {
        $this->parseArgs($args, $tokens, $pos);
        \assert(ParserAssertUtil::expect($tokens, $pos, ')'));
        ++$pos;
        $id = ParserUtil::skipFillerWs($tokens, $pos);
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
   * @param \Donquixote\QuickAttributes\Builder\Arguments\ArgumentsBuilderInterface $builder
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position of '('.
   *   After: Position of ')'.
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  private function parseArgs(ArgumentsBuilderInterface $builder, array $tokens, int &$pos): void {
    \assert(ParserAssertUtil::expect($tokens, $pos, '('));
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
          $namedPart = true;
        }
      }
      if ($key === null && $namedPart) {
        throw SyntaxException::fromTokenPos($tokens, $pos, 'Cannot use positional argument after named argument');
      }
      $id = $this->parseValueExpression(
        $builder->addArgument($key),
        $tokens,
        $pos);
      if ($id === ')') {
        break;
      }
      if ($id === ',') {
        continue;
      }
      throw SyntaxException::expectedButFound($tokens, $pos, ', or )');
    }

    \assert($tokens[$pos] === ')');
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
   * @param \Donquixote\QuickAttributes\Builder\Value\ValueBuilderInterface $result
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: First token of value expression.
   *   After: Position of ')' or ',' or '}' or ']'.
   *
   * @return string|int
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  private function parseValueExpression(ValueBuilderInterface $result, array $tokens, int &$pos) {
    $unary = '';
    $operand = $result;
    while (true) {
      $token = $tokens[$pos];
      if (\is_string($token)) {
        switch ($token) {
          case '-':
          case '!':
            $unary .= $token;
            ++$pos;
            ParserUtil::skipFillerWs($tokens, $pos);
            continue 2;

          case '(':
            ++$pos;
            ParserUtil::skipFillerWs($tokens, $pos);
            $id = $this->parseValueExpression($operand, $tokens, $pos);
            // @todo Close the value.
            \assert(ParserAssertUtil::expect($tokens, $pos, ')'));
            ++$pos;
            $id = ParserUtil::skipFillerWs($tokens, $pos);
            break;

          case '[':
            $this->parseArray($operand->startArray(), $tokens, $pos, ']');
            \assert($tokens[$pos] === ']');
            ++$pos;
            $id = ParserUtil::skipFillerWs($tokens, $pos);
            break;

          default:
            throw SyntaxException::unexpected($tokens, $pos, 'in const value expression');
        }
      }
      else {
        switch ($token[0]) {
          case \T_ARRAY:
            ++$pos;
            ParserUtil::skipFillerWsExpectChar($tokens, $pos, '(');
            $this->parseArray($operand->startArray(), $tokens, $pos, ')');
            \assert($tokens[$pos] === ')');
            ++$pos;
            $id = ParserUtil::skipFillerWs($tokens, $pos);
            break;

          case \T_LNUMBER:
          case \T_CONSTANT_ENCAPSED_STRING:
            $operand->setFixedValue(eval('return ' . $token[1] . ';'));
            ++$pos;
            /** @var string|int $id */
            $id = ParserUtil::skipFillerWs($tokens, $pos);
            break;

          case VersionDependentTokens::T_NAME_FULLY_QUALIFIED:
          case VersionDependentTokens::T_NAME_QUALIFIED:
          case VersionDependentTokens::T_STRING_8:
            $id = $this->parseConstRefPhp8($operand, $tokens, $pos);
            break;

          case VersionDependentTokens::T_STRING_7:
          case VersionDependentTokens::T_NS_SEPARATOR_7:
            $id = $this->parseConstRefPhp7($operand, $tokens, $pos);
            break;

          default:
            throw SyntaxException::expectedButFound($tokens, $pos, 'const value expression');
        }
      }
      if ($unary !== '') {
        $operand->applyUnaryOperator($unary);
      }
      \assert($id === $tokens[$pos][0]);
      if (\is_string($id)) {
        switch ($id) {
          case '-':
          case '+':
          case '*':
          case '/':
          case '.':
          case '&':
          case '|':
            // Binary operator.
            $operand = $result->appendBinaryOperator($id);
            ++$pos;
            ParserUtil::skipFillerWs($tokens, $pos);
            continue 2;

          case '[':
            // Array offset.
            // @todo Implement array offset.
            throw new \RuntimeException('Array offset not implemented.');

          case ')':
          case ']':
          case ',':
            // End of value expression.
            return $id;

          default:
            throw SyntaxException::unexpected($tokens, $pos, 'in const value expression');
        }
      }
      else {
        switch ($id) {

          case \T_DOUBLE_ARROW:
            // End of value expression.
            return $id;

          default:
            throw SyntaxException::unexpected($tokens, $pos, 'after value expression');
        }
      }
    }
  }

  /**
   * @param \Donquixote\QuickAttributes\Builder\Value\ArrayBuilderInterface $builder
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position of '(' or '['.
   *   After: Position of ')' or ']'.
   * @param string $endchar
   *   Expected end character. One of ')' or ']'.
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  private function parseArray(ArrayBuilderInterface $builder, array $tokens, int &$pos, string $endchar): void {
    \assert(ParserAssertUtil::expectOneOf($tokens, $pos, ['(', '[']));
    while (true) {
      \assert(ParserAssertUtil::expectOneOf($tokens, $pos, ['(', '[', ',']));
      ++$pos;
      $id = ParserUtil::skipFillerWs($tokens, $pos);
      if ($id === $endchar) {
        return;
      }
      $id = $this->parseValueExpression($builder->add(), $tokens, $pos);
      if ($id === \T_DOUBLE_ARROW) {
        ++$pos;
        ParserUtil::skipFillerWs($tokens, $pos);
        $id = $this->parseValueExpression($builder->mapTo(), $tokens, $pos);
      }
      if ($id === $endchar) {
        return;
      }
      if ($id !== ',') {
        throw SyntaxException::expectedButFound($tokens, $pos, "$endchar or ,");
      }
    }
  }

  /**
   * Expression referencing a global or class constant or *::class.
   *
   * @param \Donquixote\QuickAttributes\Builder\Value\ValueBuilderInterface $builder
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position of first T_STRING or T_NS_SEPARATOR.
   *   After: Non-whitespace position after last T_STRING or T_CLASS.
   *
   * @return string|int
   *   Token id following the constant expression.
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  private function parseConstRefPhp7(ValueBuilderInterface $builder, array $tokens, int &$pos) {
    \assert(\PHP_VERSION_ID < 80000);
    $startpos = $pos;
    if ($tokens[$pos][0] === \T_STRING) {
      $alias = $tokens[$pos][1];
      ++$pos;
      if ($tokens[$pos][0] === \T_NS_SEPARATOR) {
        // Resolve namespace alias.
        $qn = $this->imports[$alias] ?? ($this->terminatedNamespace . $alias);
        unset($alias);
        while (true) {
          ++$pos;
          if ($tokens[$pos][0] !== \T_STRING) {
            throw SyntaxException::expectedButFound($tokens, $pos, 'T_STRING');
          }
          $qn .= '\\' . $tokens[$pos][1];
          ++$pos;
          if ($tokens[$pos][0] !== \T_NS_SEPARATOR) {
            break;
          }
        }
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
      \assert($tokens[$pos][0] === \T_NS_SEPARATOR);
      ++$pos;
      if ($tokens[$pos][0] !== \T_STRING) {
        throw SyntaxException::expectedButFound($tokens, $pos, 'T_STRING');
      }
      $qn = $tokens[$pos][1];
      while (true) {
        ++$pos;
        if ($tokens[$pos][0] !== \T_NS_SEPARATOR) {
          break;
        }
        ++$pos;
        if ($tokens[$pos][0] !== \T_STRING) {
          throw SyntaxException::expectedButFound($tokens, $pos, 'T_STRING');
        }
        $qn .= '\\' . $tokens[$pos][1];
      }
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

  /**
   * Expression referencing a global or class constant or *::class.
   *
   * @param \Donquixote\QuickAttributes\Builder\Value\ValueBuilderInterface $builder
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *   Before: Position of first T_STRING, T_NAME_QUALIFIED or
   *     T_NAME_FULLY_QUALIFIED.
   *   After: Position of next non-whitespace token after the const reference.
   *
   * @return string|int
   *   Token id at new position.
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  private function parseConstRefPhp8(ValueBuilderInterface $builder, array $tokens, int &$pos) {
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
