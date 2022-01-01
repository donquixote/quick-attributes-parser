<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Util;

use Donquixote\QuickAttributes\Exception\ParserMalfunction;
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
   * @throws \Donquixote\QuickAttributes\Exception\ParserMalfunction
   */
  public static function expectOneIn(array $tokens, int $pos, array $map): bool {
    if ($pos < 0) {
      $pos += \count($tokens);
    }
    if (isset($map[$tokens[$pos][0]])) {
      return true;
    }
    $parts = [];
    foreach ($map as $k => $_) {
      $parts[] = \is_int($k)
        ? \token_name($k)
        : \var_export($k, true);
    }
    $export = \implode(' or ', $parts);
    throw self::expectedButFound($tokens, $pos, $export);
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   * @param int|string $expected
   *
   * @return true
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserMalfunction
   */
  public static function expect(array $tokens, int $pos, $expected): bool {
    if ($pos < 0) {
      $pos += \count($tokens);
    }
    if ($tokens[$pos][0] === $expected) {
      return true;
    }
    $export = \is_int($expected)
      ? \token_name($expected)
      : \var_export($expected, true);
    throw self::expectedButFound($tokens, $pos, $export);
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   * @param array-key[] $allowed
   *
   * @return true
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserMalfunction
   */
  public static function expectOneOf(array $tokens, int $pos, array $allowed): bool {
    return self::expectOneIn($tokens, $pos, \array_fill_keys($allowed, true));
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   * @param string $expected
   *
   * @return \Donquixote\QuickAttributes\Exception\ParserMalfunction
   */
  private static function expectedButFound(array $tokens, int $pos, string $expected): ParserMalfunction {
    // Borrow from SyntaxException to generate the message.
    $message = SyntaxException::expectedButFound($tokens, $pos, $expected)->getMessage();
    return new ParserMalfunction($message);
  }

}
