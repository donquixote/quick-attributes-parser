<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Util;

class TokenizerUtil {

  private const REGEX = '@
# Non-word char to isolate the subsequent keyword.
\W
(?:abstract\s+class|final\s+class|class|interface|trait)
# Whitespace before the class shortname.
\s+
# The class shortname.
[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*
# Non-word character after the class name. Capture offset here.
(\W)
@sx';

  /**
   * Tokenizes php from a file in two versions.
   *
   * This is useful for applications that only need the class header,
   *
   * @param string $php
   *   PHP read from a file.
   *
   * @return \Iterator<list<string|array{int, string, int}>>
   *   For a class file:
   *     1. All tokens until (including) the class header.
   *     2. All tokens in the file.
   *   For any other file:
   *     1. All tokens in the file.
   */
  public static function tokenizeClassFileContents(string $php): \Iterator {
    $offset = 0;
    while (TRUE) {
      // Look for class name.
      if (!\preg_match(
        self::REGEX,
        $php,
        $m,
        \PREG_OFFSET_CAPTURE,
        $offset)
      ) {
        // Don't treat this likea class file.
        $tokens = \token_get_all($php);
        $tokens[] = '#';
        yield $tokens;
        return;
      }

      /** @var list<array{string, int}> $m */
      $headLength = $m[1][1];
      $phpHead = \substr($php, 0, $headLength);
      $tokensHead = token_get_all($phpHead);
      // Verify the regex was not tricked by a comment or string literal.
      if (\end($tokensHead)[0] === \T_STRING) {
        break;
      }
      // The regex must have found a comment or string literal.
    }

    $tokens = $tokensHead;
    $tokens[] = '#';
    $remainingOffset = $headLength;

    // Yield a temporary tokens array that ends with the class shortname.
    yield $tokens;

    // Tokenize the rest of the file.
    // Make sure to have at least one whitespace token after `<?php`.
    $phpRemaining = '<?php  '
      // Prepend new lines to make sure line numbers match up.
      . \str_repeat(
        "\n",
        \substr_count($phpHead, "\n"))
      // Prepend a ';' to isolate whitespace before and after.
      . ';'
      . \substr($php, $remainingOffset);

    $tokensRemaining = \token_get_all($phpRemaining);

    // Combine old and new tokens.
    $tokens = [
      ...\array_slice($tokens, 0, -1),
      ...\array_slice($tokensRemaining, 3),
      '#',
    ];

    \assert($tokens === [...\token_get_all($php), '#']);

    // Yield the final tokens array.
    yield $tokens;
  }

}
