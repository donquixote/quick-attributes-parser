<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Exception;

use Donquixote\QuickAttributes\Util\ParserUtil;
use Donquixote\QuickAttributes\Util\TokenPositionUtil;
use Throwable;

/**
 * @psalm-consistent-constructor
 */
class ParserException extends \Exception {

  /**
   * Constructor.
   *
   * This explicit override is needed for psalm.
   * See https://github.com/vimeo/psalm/issues/6627
   */
  public function __construct(string $message = "", int $code = 0, Throwable $previous = NULL) {
    parent::__construct($message, $code, $previous);
  }

  /**
   * Prepends source file name to the message.
   *
   * @param string $file
   * @param string|null $basedir
   * @param int $startLine
   * @param int $indent
   */
  public function setSourceFile(string $file, string $basedir = NULL, int $startLine = 0, int $indent = 0): void {
    if ($basedir !== NULL && \preg_match(
      '@^' . \preg_quote($basedir  . '/', '@') . '(.*)$@',
      $file,
      $m)
    ) {
      $file = $m[1];
    }
    if (\preg_match('@^Line (\d+)\:(\d+)\: (.*)$@', $this->message, $m)) {
      $line = (int) $m[1] + $startLine;
      $pos = (int) $m[2] + $indent;
      $message = $m[3];
      $this->message = "In $file:$line:$pos: $message";
    }
    else {
      $this->message .= "\nParsed source file: $file.";
    }
  }

  /**
   * Static factory.
   *
   * @param list<string|array{int, string, int}> $tokens
   * @param int $pos
   * @param string $message
   * @param bool $append_found
   *
   * @return static
   *
   * @noinspection PhpMissingReturnTypeInspection
   */
  public static function fromTokenPos(array $tokens, int $pos, string $message, bool $append_found = FALSE) {
    [$line, $chrpos] = TokenPositionUtil::findLineChrPos($tokens, $pos);
    $message = "Line $line:$chrpos: $message";
    if ($append_found) {
      $message .= ' Found ' . ParserUtil::formatToken($tokens[$pos]) . '.';
    }
    return new static($message);
  }

}
