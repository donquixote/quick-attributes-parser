<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Util;

use Donquixote\QuickAttributes\Exception\SyntaxException;

/**
 * Methods to call within assert(), to verify correctness of the parser.
 */
class ParserAssertUtil {

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   * @param array $map
   *
   * @return true
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  public static function expectOneIn(array $tokens, int $pos, array $map): bool {
    if (isset($map[$tokens[$pos][0]])) {
      return TRUE;
    }
    $parts = [];
    foreach ($map as $k => $_) {
      $parts[] = \is_int($k)
        ? \token_name($k)
        : \var_export($k, TRUE);
    }
    $export = \implode(' or ', $parts);
    throw SyntaxException::expectedButFound($tokens, $pos, $export);
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   * @param int|string $expected
   *
   * @return true
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  public static function expect(array $tokens, int $pos, $expected): bool {
    if ($tokens[$pos][0] === $expected) {
      return TRUE;
    }
    $export = \is_int($expected)
      ? \token_name($expected)
      : \var_export($expected, TRUE);
    throw SyntaxException::expectedButFound($tokens, $pos, $export);
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   * @param array-key[] $allowed
   *
   * @return true
   *
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  public static function expectOneOf(array $tokens, int $pos, array $allowed): bool {
    return self::expectOneIn($tokens, $pos, \array_fill_keys($allowed, TRUE));
  }

}
