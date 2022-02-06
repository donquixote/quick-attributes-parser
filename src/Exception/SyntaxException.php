<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Exception;

use Donquixote\QuickAttributes\Util\ParserUtil;

class SyntaxException extends ParserException {

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   * @param string $where
   *
   * @return static
   */
  public static function unexpected(array $tokens, int $pos, string $where): self {
    return static::fromTokenPos(
      $tokens,
      $pos,
      \sprintf(
        'Unexpected %s %s.',
        ParserUtil::formatToken($tokens[$pos]),
        $where));
  }

  /**
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   * @param string $expected
   *
   * @return static
   */
  public static function expectedButFound(array $tokens, int $pos, string $expected): self {
    return static::fromTokenPos(
      $tokens,
      $pos,
      \sprintf(
        'Expected %s, but found %s.',
        $expected,
        ParserUtil::formatToken($tokens[$pos])));
  }

}
