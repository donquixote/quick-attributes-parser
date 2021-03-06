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
   *   Both start at 1.
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
   * @return array{int, int}
   *   Format: [$line, $chrpos].
   *   Both start at 1.
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
        if (false !== $nlpos = \strrpos($tokens[$i][1], "\n")) {
          return [
            $tokens[$i][2] + \substr_count($tokens[$i][1], "\n"),
            $chrpos - $nlpos,
          ];
        }
      }
    }
    return [1, $chrpos + 1];
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
   *   Line number, starting from 1.
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
    return 1;
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
   *   Position of the token in the line, starting from 1.
   */
  public static function findChrPos(array $tokens, int $pos): int {
    $chrpos = 0;
    for ($i = $pos - 1; $i >= 0; --$i) {
      if (!\is_array($tokens[$i])) {
        $chrpos += \strlen($tokens[$i]);
      }
      else {
        $chrpos += \strlen($tokens[$i][1]);
        if (false !== $nlpos = \strrpos($tokens[$i][1], "\n")) {
          return $chrpos - $nlpos;
        }
      }
    }
    return $chrpos + 1;
  }

}
