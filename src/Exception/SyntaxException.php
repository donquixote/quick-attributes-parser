<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Exception;

use Donquixote\QuickAttributes\Util\ParserUtilPhp7;

class SyntaxException extends ParserException {

  /**
   * @param array $tokens
   * @param int $pos
   * @param string $where
   *
   * @return static
   */
  public static function unexpected(array $tokens, int $pos, string $where): self {
    return static::fromTokenPos(
      $tokens,
      $pos,
      sprintf(
        'Unexpected %s %s.',
        ParserUtilPhp7::formatToken($tokens[$pos]),
        $where),
      FALSE);
  }

  /**
   * @param array $tokens
   * @param int $pos
   * @param string|null $preceding
   *
   * @return static
   */
  public static function unexpectedAfter(array $tokens, int $pos, string $preceding = NULL): self {
    return static::fromTokenPos(
      $tokens,
      $pos,
      \vsprintf('Unexpected %s after %s.', [
        ParserUtilPhp7::formatToken($tokens[$pos]),
        $preceding ?? ParserUtilPhp7::formatToken($tokens[$pos - 1]),
      ]),
      FALSE);
  }

  /**
   * @param array $tokens
   * @param int $pos
   * @param string $expected
   *
   * @return static
   */
  public static function expectedButFound(array $tokens, int $pos, string $expected): self {
    return static::fromTokenPos(
      $tokens,
      $pos,
      sprintf(
        'Expected %s, but found %s.',
        $expected,
        ParserUtilPhp7::formatToken($tokens[$pos])),
      FALSE);
  }

}
