<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Util;

class TokenPositionUtil {

  /**
   * Builds a formatted string from line number and character position.
   *
   * @param list<string|array{int, string, int}> $tokens
   *   Tokens from token_get_all(), with appended terminating '#'.
   * @param int $pos
   *   Token index.
   *
   * @return string
   *   Format: "$line:$chrpos".
   */
  public static function formatLineChrPos(array $tokens, int $pos): string {
    [$line, $chrpos] = self::findLineChrPos($tokens, $pos);
    return "$line:$chrpos";
  }

  /**
   * Finds line number and character position of a token.
   *
   * @param list<string|array{int, string, int}> $tokens
   *   Tokens from token_get_all(), with appended terminating '#'.
   * @param int $pos
   *   Token index.
   *
   * @return int[]
   *   Format: [$line, $chrpos].
   *
   * @see findLineNumber()
   * @see findChrPos()
   */
  public static function findLineChrPos(array $tokens, int $pos): array {
    $chrpos = 0;
    for ($i = $pos - 1; $i >= 0; --$i) {
      if (!\is_array($tokens[$i])) {
        // Non-array tokens never have line breaks.
        $chrpos += \strlen($tokens[$i]);
      }
      else {
        $chrpos += \strlen($tokens[$i][1]);
        if (FALSE !== $nlpos = \strrpos($tokens[$i][1], "\n")) {
          return [
            $tokens[$i][2] + \substr_count($tokens[$i][1], "\n"),
            $chrpos - $nlpos,
          ];
        }
      }
    }
    return [0, $chrpos];
  }

  /**
   * Finds the line number of a token.
   *
   * @param list<string|array{int, string, int}> $tokens
   *   Tokens from token_get_all(), with appended terminating '#'.
   * @param int $pos
   *   Token index.
   *
   * @return int
   *   Line number.
   */
  public static function findLineNumber(array $tokens, int $pos): int {
    for ($i = $pos; $tokens[$i] !== '#'; ++$i) {
      if (\is_array($tokens[$i])) {
        return $tokens[$i][2];
      }
    }
    for ($i = $pos - 1; $i >= 0; --$i) {
      if (\is_array($tokens[$i])) {
        return $tokens[$i][2] + \substr_count($tokens[$i][1], "\n");
      }
    }
    return 0;
  }

  /**
   * Finds the token character position within its line of code.
   *
   * @param list<string|array{int, string, int}> $tokens
   *   Tokens from token_get_all(), with appended terminating '#'.
   * @param int $pos
   *   Token index.
   *
   * @return int
   *   Number of chars in same line before the token.
   */
  public static function findChrPos(array $tokens, int $pos): int {
    $chrpos = 0;
    for ($i = $pos - 1; $i >= 0; --$i) {
      if (!\is_array($tokens[$i])) {
        $chrpos += \strlen($tokens[$i]);
      }
      else {
        $chrpos += \strlen($tokens[$i][1]);
        if (FALSE !== $nlpos = \strrpos($tokens[$i][1], "\n")) {
          return $chrpos - $nlpos;
        }
      }
    }
    return $chrpos;
  }

}
