<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\FileTokens;

use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\Util\TokenizerUtil;

class FileTokens_Common implements FileTokensInterface {

  private string $php;

  /**
   * Constructor.
   *
   * @param string $php
   */
  public function __construct(string $php) {
    $this->php = $php;
  }

  /**
   * @param string $file
   *
   * @return self
   */
  public static function fromFile(string $file): self {
    return new self(
      \file_get_contents($file));
  }

  private static function getRegex(): string {
    /** @var ?string $regex */
    static $regex;
    if ($regex !== NULL) {
      return $regex;
    }
    $shortname = '[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*';
    $qcn = $shortname . '(?:\\\\' . $shortname . ')*';
    $name = '\\\\?' . $qcn;
    $names = $name . '(?:\s*,\s*' . $name . ')*';
    return $regex = strtr('@
# Non-word char to isolate the subsequent keyword.
\W
(?:abstract\s+class|final\s+class|class|interface|trait)
# Whitespace before the class shortname.
\s+
# The class shortname.
%shortname
(?:|\s+extends\s+%names)
(?:|\s+implements\s+%names)
# Capture offset at opening curly bracket of class body.
\s*(\{)
@sx', [
      '%shortname' => $shortname,
      '%names' => $names,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getTokenss(): \Iterator {
    $offset = 0;
    while (TRUE) {
      // Look for class name.
      if (!\preg_match(
        self::getRegex(),
        $this->php,
        $m,
        \PREG_OFFSET_CAPTURE,
        $offset)
      ) {
        // Tokenize this file all at once, don't split off the file head.
        $tokens = TokenizerUtil::tokenGetAll($this->php);
        $tokens[] = '#';
        yield $tokens;
        return;
      }

      /** @var list<array{string, int}> $m */
      $openCurlyPos = $m[1][1];
      \assert($this->php[$openCurlyPos] === '{');
      // Include the open curly bracket in the file head, to act as a marker.
      $phpHead = \substr($this->php, 0, $openCurlyPos + 1);
      // Append a comment to prevent un-catchable E_COMPILE_WARNING.
      $phpHead .= '/* */';
      $tokensHead = TokenizerUtil::tokenGetAll($phpHead);
      $nTokensHead = count($tokensHead);
      // Verify the regex was not tricked by a comment or string literal.
      if ($tokensHead[$nTokensHead - 2] === '{') {
        \assert($tokensHead[$nTokensHead - 1][0] === T_COMMENT);
        \assert($tokensHead[$nTokensHead - 1][1] === '/* */');
        // Remove the '/* */' comment.
        \array_pop($tokensHead);
        break;
      }
      // The regex must have found a comment or string literal.
      $offset = $openCurlyPos;
    }

    $tokens = $tokensHead;
    $tokens[] = '#';

    // Yield a temporary tokens array that ends with the class shortname.
    yield $tokens;

    // Tokenize the rest of the file.
    // Make sure to have at least one whitespace token after `<?php`.
    $phpRemaining = '<?php  '
      // Prepend new lines to make sure line numbers match up.
      . \str_repeat(
        "\n",
        \substr_count($phpHead, "\n"))
      // Include the open curly bracket.
      . \substr($this->php, $openCurlyPos);

    $tokensRemaining = TokenizerUtil::tokenGetAll($phpRemaining);

    // Combine old and new tokens.
    $tokens = [
      ...\array_slice($tokens, 0, -1),
      ...\array_slice($tokensRemaining, 3),
      '#',
    ];

    \assert($tokens === [...TokenizerUtil::tokenGetAll($this->php), '#']);

    // Yield the final tokens array.
    yield $tokens;
  }

}
