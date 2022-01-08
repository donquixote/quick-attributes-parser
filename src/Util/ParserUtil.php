<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Util;

use Donquixote\QuickAttributes\Exception\SyntaxException;

class ParserUtil {

  const SPECIAL_TOKEN_NAMES = [
    VersionDependentTokens::T_ATTRIBUTE => 'T_ATTRIBUTE',
    VersionDependentTokens::T_NAME_QUALIFIED => 'T_NAME_QUALIFIED',
    VersionDependentTokens::T_NAME_FULLY_QUALIFIED => 'T_NAME_FULLY_QUALIFIED',
  ];

  const ACCESS_MODIFIERS = [
    \T_PUBLIC => 'public',
    \T_PROTECTED => 'protected',
    \T_PRIVATE => 'private',
  ];

  const IDENTIFIER_START_TOKENS = (\PHP_VERSION_ID < 80000)
    ? [
      \T_STRING => true,
      \T_NS_SEPARATOR => true,
    ]
    : [
      \T_STRING => true,
      VersionDependentTokens::T_NAME_FULLY_QUALIFIED => true,
      VersionDependentTokens::T_NAME_QUALIFIED => true,
    ];

  const WS_MAPS = [
    \T_WHITESPACE => [
      \T_WHITESPACE => true,
    ],
    \T_COMMENT => [
      \T_WHITESPACE => true,
      \T_COMMENT => true,
    ],
    \T_DOC_COMMENT => [
      \T_WHITESPACE => true,
      \T_COMMENT => true,
      \T_DOC_COMMENT => true,
    ],
  ];

  const WS_MAP = [
    \T_WHITESPACE => true,
  ];

  const WS_OR_COMMENT = [
    \T_WHITESPACE => true,
    \T_COMMENT => true,
  ];

  const WS_OR_DOC = [
    \T_WHITESPACE => true,
    \T_DOC_COMMENT => true,
  ];

  const WS_OR_COMMENT_OR_DOC = [
    \T_WHITESPACE => true,
    \T_COMMENT => true,
    \T_DOC_COMMENT => true,
  ];

  /** @var (-1|0|1)[] */
  private const SKIP_CURLY_MAP = [
    '{' => 1,
    \T_CURLY_OPEN => 1,
    \T_DOLLAR_OPEN_CURLY_BRACES => 1,
    '}' => -1,
    // End of file marker.
    '#' => 0,
  ];

  /** @var (-1|0|1)[] */
  private const SKIP_SQUARE_MAP = [
    '[' => 1,
    VersionDependentTokens::T_ATTRIBUTE => 1,
    ']' => -1,
    // End of file marker.
    '#' => 0,
  ];

  /** @var (-1|0|1)[] */
  private const SKIP_PARENS_MAP = [
    '(' => 1,
    ')' => -1,
    // End of file marker.
    '#' => 0,
  ];

  /** @var (-1|0|1)[][] */
  public const SKIP_MAP = [
    '{' => self::SKIP_CURLY_MAP,
    \T_CURLY_OPEN => self::SKIP_CURLY_MAP,
    \T_DOLLAR_OPEN_CURLY_BRACES => self::SKIP_CURLY_MAP,
    '(' => self::SKIP_PARENS_MAP,
    '[' => self::SKIP_SQUARE_MAP,
    VersionDependentTokens::T_ATTRIBUTE => self::SKIP_SQUARE_MAP,
  ];

  /** @var (-1|0|1)[][] */
  public const SKIP_MAP_REVERSE = [
    '}' => self::SKIP_CURLY_MAP,
    ')' => self::SKIP_PARENS_MAP,
    ']' => self::SKIP_SQUARE_MAP,
  ];

  public const CLASS_LIKE_TOKENS = [
    \T_CLASS => 'class',
    \T_INTERFACE => 'interface',
    \T_TRAIT => 'trait',
  ];

  /**
   * Skips a code section with '(..)'.
   *
   * It is assumed that the code in between is valid.
   *
   * @param list<string|array{int, string, int}> $tokens
   *   Tokens from token_get_all(), with terminating '#'.
   * @param int $pos
   *   Before: Position of the opening '(', '[' or '{'.
   *   After (success): Position of the closing ')', ']' or '}'.
   *   After (failure): Original position.
   *
   * @return void
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  public static function skipSubtree(array $tokens, int &$pos): void {
    /** @var (-1|0|1)[]|null $map */
    $map = self::SKIP_MAP[$tokens[$pos][0]] ?? null;
    if ($map === null) {
      throw new \RuntimeException(
        'skipSubtree() was called on an invalid position.');
    }
    $level = 0;
    for ($i = $pos + 1; ; ++$i) {
      if (!isset($map[$tokens[$i][0]])) {
        // Ignore this token.
        // This is the most frequent case, to be optimized for.
        continue;
      }
      if ($tokens[$i] === '#') {
        throw SyntaxException::fromTokenPos(
          $tokens,
          $i,
          'Unexpected end of file in nested structure.');
      }
      $level += $map[$tokens[$i][0]];
      if ($level < 0) {
        // Set new position.
        $pos = $i;
        return;
      }
    }
  }

  /**
   * Skips a code section with '".."'.
   *
   * It is assumed that the code in between is valid.
   *
   * @param list<string|array{int, string, int}> $tokens
   *   Tokens from token_get_all(), with terminating '#'.
   * @param int $pos
   *   Before: Position of the opening '"'.
   *   After (success): Position of the closing '"'.
   *   After (failure): Original position.
   *
   * @return void
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  public static function skipDoubleQuotedString(array $tokens, int &$pos): void {
    $i = $pos;
    while (true) {
      ++$i;
      if ($tokens[$i] === '"') {
        break;
      }
      if ($tokens[$i] === '#') {
        throw new SyntaxException('Unexpected EOF in string.');
      }
    }
    $pos = $i;
  }

  /**
   * Skips a code section with '(..)', backwards.
   *
   * It is assumed that the code in between is valid.
   *
   * @param list<string|array{int, string, int}> $tokens
   *   Tokens from token_get_all(), with terminating '#'.
   * @param int $pos
   *   Before: Position of the closing ')', ']' or '}'.
   *   After (success): Position of opening '(', '[' or '{'.
   *   After (failure): Original position.
   *
   * @return void
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  public static function skipSubtreeReverse(array $tokens, int &$pos): void {
    /** @var (1|0|-1)[]|null $map */
    $map = self::SKIP_MAP[$tokens[$pos][0]] ?? null;
    if ($map === null) {
      throw new \RuntimeException(
        'skipSubtree() was called on an invalid position.');
    }
    $level = 0;
    for ($i = $pos - 1; $i >= 0; --$i) {
      if (!isset($map[$tokens[$i][0]])) {
        // Ignore this token.
        // This is the most frequent case, to be optimized for.
        continue;
      }
      if ($tokens[$i] === '#') {
        throw SyntaxException::fromTokenPos(
          $tokens,
          $i,
          'Unexpected end of file in nested structure.');
      }
      $level -= $map[$tokens[$i][0]];
      if ($level < 0) {
        // Set new position.
        $pos = $i;
        return;
      }
    }
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   * @param int $expected
   *
   * @return string
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  public static function skipFillerWsExpectToken(array $tokens, int &$pos, int $expected): string {
    $id = self::skipFillerWs($tokens, $pos);
    if ($id !== $expected) {
      throw SyntaxException::expectedButFound($tokens, $pos, \token_name($expected));
    }
    return $tokens[$pos][1];
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *
   * @return string
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  public static function skipFillerWsExpectTString(array $tokens, int &$pos): string {
    $id = self::skipFillerWs($tokens, $pos);
    if ($id !== \T_STRING) {
      throw SyntaxException::expectedButFound($tokens, $pos, 'T_STRING');
    }
    return $tokens[$pos][1];
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *
   * @return string
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  public static function skipFillerWsExpectMemberName(array $tokens, int &$pos): string {
    $id = self::skipFillerWs($tokens, $pos);
    if ($id !== \T_STRING) {
      $token = $tokens[$pos];
      if (!\is_array($token)
        || !ReservedWordUtil::validMemberName($tokens[$pos][1])
      ) {
        throw SyntaxException::expectedButFound($tokens, $pos, 'T_STRING');
      }
    }
    return $tokens[$pos][1];
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   * @param string $expected
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  public static function skipFillerWsExpectChar(array $tokens, int &$pos, string $expected): void {
    $id = self::skipFillerWs($tokens, $pos);
    if ($id !== $expected) {
      throw SyntaxException::expectedButFound($tokens, $pos, $expected);
    }
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   *
   * @return string|int
   *   Token id at the new position.
   */
  public static function skipFillerWs(array $tokens, int &$pos) {
    $i = $pos;
    while (true) {
      $id = $tokens[$i][0];
      if ($id === \T_COMMENT) {
        if (\PHP_VERSION_ID < 80000 && $tokens[$i][1][1] === '[') {
          // Found an attribute.
          $pos = $i;
          return $id;
        }
      }
      elseif ($id !== \T_DOC_COMMENT && $id !== \T_WHITESPACE) {
        $pos = $i;
        return $id;
      }
      ++$i;
    }
  }

  /**
   * @param string|array{int, string, int} $token
   *
   * @return string
   */
  public static function formatToken($token): string {
    if (\is_array($token)) {
      $name = \token_name($token[0]);
      if ($name === 'UNKNOWN') {
        $name = self::SPECIAL_TOKEN_NAMES[$token[0]] ?? $name;
      }
      return $name . ' / ' . \var_export($token[1], true);
    }
    if ($token === '#') {
      return 'EOF';
    }
    return \var_export($token, true);
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $begin
   * @param int $end
   *
   * @return string
   */
  public static function concatTokens(array $tokens, int $begin, int $end): string {
    $code = '';
    for ($i = $begin; $i < $end; ++$i) {
      if (\is_string($tokens[$i])) {
        $code .= $tokens[$i];
      }
      else {
        $code .= $tokens[$i][1];
      }
    }
    return $code;
  }

}
