<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Util;

use Donquixote\QuickAttributes\Exception\SyntaxException;

/**
 * This class should only be used in PHP < 8.
 */
class ParserUtilPhp7 {

  const ACCESS_MODIFIERS = [
    T_PUBLIC => 'public',
    T_PROTECTED => 'protected',
    T_PRIVATE => 'private',
  ];

  const IDENTIFIER_START_TOKENS = (PHP_VERSION_ID < 80000)
    ? [
      T_STRING => TRUE,
      T_NS_SEPARATOR => TRUE,
    ]
    : [
      T_STRING => TRUE,
    ];

  const WS_MAPS = [
    T_WHITESPACE => [
      T_WHITESPACE => TRUE,
    ],
    T_COMMENT => [
      T_WHITESPACE => TRUE,
      T_COMMENT => TRUE,
    ],
    T_DOC_COMMENT => [
      T_WHITESPACE => TRUE,
      T_COMMENT => TRUE,
      T_DOC_COMMENT => TRUE,
    ],
  ];

  const WS_MAP = [
    T_WHITESPACE => TRUE,
  ];

  const WS_OR_COMMENT = [
    T_WHITESPACE => TRUE,
    T_COMMENT => TRUE,
  ];

  const WS_OR_DOC = [
    T_WHITESPACE => TRUE,
    T_DOC_COMMENT => TRUE,
  ];

  const WS_OR_COMMENT_OR_DOC = [
    T_WHITESPACE => TRUE,
    T_COMMENT => TRUE,
    T_DOC_COMMENT => TRUE,
  ];

  private const SKIP_CURLY_MAP = [
    '{' => 1,
    T_CURLY_OPEN => 1,
    T_DOLLAR_OPEN_CURLY_BRACES => 1,
    '}' => -1,
    // End of file marker.
    '#' => 0,
  ];

  private const SKIP_SQUARE_MAP = [
    '[' => 1,
    ']' => -1,
    // End of file marker.
    '#' => 0,
  ];

  private const SKIP_PARENS_MAP = [
    '(' => 1,
    ')' => -1,
    // End of file marker.
    '#' => 0,
  ];

  public const SKIP_MAP = [
    '{' => self::SKIP_CURLY_MAP,
    T_CURLY_OPEN => self::SKIP_CURLY_MAP,
    T_DOLLAR_OPEN_CURLY_BRACES => self::SKIP_CURLY_MAP,
    '(' => self::SKIP_PARENS_MAP,
    '[' => self::SKIP_SQUARE_MAP,
  ];

  public const SKIP_MAP_REVERSE = [
    '}' => self::SKIP_CURLY_MAP,
    ')' => self::SKIP_PARENS_MAP,
    ']' => self::SKIP_SQUARE_MAP,
  ];

  public const CLASS_LIKE_TOKENS = [
    T_CLASS => 'class',
    T_INTERFACE => 'interface',
    T_TRAIT => 'trait',
  ];

  /**
   * Skips a code section with '(..)'.
   *
   * It is assumed that the code in between is valid.
   *
   * @param array $tokens
   *   Tokens from token_get_all(), with terminating '#'.
   * @param int $pos
   *   Before: Position of the opening '(', '[' or '{'.
   *   After (success): Position after the closing ')', ']' or '}'.
   *   After (failure): Original position.
   *
   * @return void
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  public static function skipSubtree(array $tokens, int &$pos): void {
    $map = self::SKIP_MAP[$tokens[$pos]] ?? NULL;
    if ($map === NULL) {
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
        $pos = $i + 1;
        return;
      }
    }
  }

  /**
   * Skips a code section with '".."'.
   *
   * It is assumed that the code in between is valid.
   *
   * @param array $tokens
   *   Tokens from token_get_all(), with terminating '#'.
   * @param int $pos
   *   Before: Position of the opening '"'.
   *   After (success): Position after the closing ')', ']' or '}'.
   *   After (failure): Original position.
   *
   * @return void
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  public static function skipDoubleQuotedString(array $tokens, int &$pos): void {
    $i = $pos;
    while (TRUE) {
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
   * @param array $tokens
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
    $map = self::SKIP_MAP[$tokens[$pos]] ?? NULL;
    if ($map === NULL) {
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
   * @param array $tokens
   * @param int $pos
   * @param string|null $docComment
   *
   * @return mixed|void
   */
  public static function skipHeaderWs(array $tokens, int &$pos, string &$docComment = NULL) {
    for ($i = $pos;; ++$i) {
      $id = $tokens[$i][0];
      if ($id === T_COMMENT) {
        if (PHP_VERSION_ID < 80000 && substr($tokens[$i][1], 0, 2) === '#[') {
          // Found a
          $pos = $i;
          return $id;
        }
      }
      elseif ($id === T_DOC_COMMENT) {
        $docComment = $tokens[$i][1];
      }
      elseif ($id !== T_WHITESPACE) {
        $pos = $i;
        return $id;
      }
    }
  }

  /**
   * @param array $tokens
   * @param int $pos
   * @param int $expected
   *
   * @return string
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  public static function skipFillerWsExpectToken(array $tokens, int &$pos, int $expected): string {
    $id = self::skipFillerWs($tokens, $pos);
    if ($id !== $expected) {
      throw SyntaxException::expectedButFound($tokens, $pos, token_name($expected));
    }
    return $tokens[$pos][1];
  }

  /**
   * @param array $tokens
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
   * @param array $tokens
   * @param int $pos
   *
   * @return string|int
   *   Token id at the new position.
   */
  public static function skipFillerWs(array $tokens, int &$pos) {
    for ($i = $pos;; ++$i) {
      $id = $tokens[$i][0];
      if ($id === T_COMMENT) {
        if (PHP_VERSION_ID < 80000 && $tokens[$i][1][1] === '[') {
          // Found an attribute.
          $pos = $i;
          return $id;
        }
      }
      elseif ($id !== T_DOC_COMMENT && $id !== T_WHITESPACE) {
        $pos = $i;
        return $id;
      }
    }
  }

  /**
   * @param array $tokens
   * @param int $i
   * @param true[] $skipMap
   *   Format: $[$token_id] = TRUE.
   *
   * @return string|int
   */
  public static function nextNonWsIncl(
    array $tokens,
    int &$i,
    array $skipMap = [
      T_WHITESPACE => TRUE,
      T_COMMENT => TRUE,
    ]
  ) {
    while (TRUE) {
      if (!isset($skipMap[$tokens[$i][0]])) {
        return $tokens[$i][0];
      }
      ++$i;
    }
  }

  /**
   * @param array $tokens
   * @param int $i
   * @param true[] $skipMap
   *   Format: $[$token_id] = TRUE.
   *
   * @return string|int
   */
  public static function nextNonWsExcl(
    array $tokens,
    int &$i,
    array $skipMap = [
      T_WHITESPACE => TRUE,
      T_COMMENT => TRUE,
    ]
  ) {
    while (TRUE) {
      ++$i;
      if (!isset($skipMap[$tokens[$i][0]])) {
        return $tokens[$i][0];
      }
    }
  }

  /**
   * @param array $tokens
   * @param int $i
   *
   * @return string[]
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  public static function parseIdentifierList(array $tokens, int &$i): array {
    $list = array();
    while (TRUE) {
      $id = self::nextNonWsIncl($tokens, $i);
      if (T_STRING !== $id) {
        throw SyntaxException::expectedButFound($tokens, $i, 'identifier.');
      }
      $list[] = $tokens[$i][1];
      $id = self::nextNonWsExcl($tokens, $i);
      if (',' !== $id) {
        break;
      }
      ++$i;
    }
    return $list;
  }

  /**
   * @param array $tokens
   * @param int $pos
   *   Before: Position of the first part of the qcn.
   *   After (success): Position after the last part of the qcn.
   *   After (failure): Original position.
   *
   * @return string
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  public static function parseQcn(array $tokens, int &$pos): string {
    if (PHP_VERSION_ID >= 80000) {
      $id = $tokens[$pos][0];
      if ($id !== T_STRING && $id !== self::T_NAME_QUALIFIED) {
        throw SyntaxException::expectedButFound($tokens, $pos, 'qualified name');
      }
      $qcn = $tokens[$pos][1];
      ++$pos;
      return $qcn;
    }
    return self::parseQcnBc($tokens, $pos);
  }

  /**
   * @param array $tokens
   * @param int $pos
   *   Before: Position of the first part of the qcn.
   *   After (success): Position after the last part of the qcn.
   *   After (failure): Original position.
   *
   * @return string
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  private static function parseQcnBc(array $tokens, int &$pos): string {
    $qcn = '';
    for ($i = $pos; ; ++$i) {
      $id = $tokens[$i][0];
      if ($id !== T_STRING) {
        throw SyntaxException::expectedButFound($tokens, $pos, 'identifier');
      }
      // @todo Validate that string is valid identifier part?
      $qcn .= $tokens[$i][1];
      ++$i;
      if ($tokens[$i][0] !== T_NS_SEPARATOR) {
        $pos = $i;
        return $qcn;
      }
      $qcn .= '\\';
    }
  }

  /**
   * @param string|array $token
   *
   * @return string
   */
  public static function formatToken($token): string {
    if (is_array($token)) {
      return token_name($token[0]) . ' / ' . var_export($token[1], TRUE);
    }
    if ($token === '#') {
      return 'EOF';
    }
    return var_export($token, TRUE);
  }

  /**
   * @param array $tokens
   * @param int $pos
   * @param string|null $class
   * @param string $terminatedNamespace
   * @param array $imports
   *
   * @return string
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  public static function parseAliasBc(array $tokens, int &$pos, ?string $class, string $terminatedNamespace, array $imports): string {
    assert(self::expect($tokens, $pos, T_STRING));
    $name = $tokens[$pos][1];
    if (strtolower($name) === 'self') {
      if ($class === NULL) {
        throw SyntaxException::unexpected($tokens, $pos, 'outside of a class');
      }
      ++$pos;
      return $class;
    }
    $name = $imports[$name] ?? $terminatedNamespace . $name;
    if ($tokens[$pos + 1][0] !== T_NS_SEPARATOR) {
      return $name;
    }
    for ($i = $pos + 2;; ++$i) {
      if ($tokens[$i][0] !== T_STRING) {
        throw SyntaxException::expectedButFound($tokens, $pos, 'T_STRING');
      }
      // @todo Validate that string is valid identifier part?
      $name .= $tokens[$i][1];
      ++$i;
      if ($tokens[$i][0] !== T_NS_SEPARATOR) {
        return $name;
      }
      $name .= '\\';
    }
  }

  /**
   * @param array $tokens
   * @param int $pos
   * @param int|string $expected
   *
   * @return bool
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  public static function expect(array $tokens, int $pos, $expected): bool {
    if ($tokens[$pos][0] === $expected) {
      return TRUE;
    }
    $export = is_int($expected)
      ? token_name($expected)
      : var_export($expected, TRUE);
    throw SyntaxException::expectedButFound($tokens, $pos, $export);
  }

  /**
   * @param array $tokens
   * @param int $pos
   * @param array $allowed
   *
   * @return bool
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  public static function expectOneOf(array $tokens, int $pos, array $allowed): bool {
    return self::expectOneIn($tokens, $pos, array_fill_keys($allowed, TRUE));
  }

  /**
   * @param array $tokens
   * @param int $pos
   * @param array $map
   *
   * @return bool
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  public static function expectOneIn(array $tokens, int $pos, array $map): bool {
    if (isset($map[$tokens[$pos][0]])) {
      return TRUE;
    }
    $parts = [];
    foreach ($map as $k => $_) {
      $parts[] = is_int($k)
        ? token_name($k)
        : var_export($k, TRUE);
    }
    $export = implode(' or ', $parts);
    throw SyntaxException::expectedButFound($tokens, $pos, $export);
  }

}
