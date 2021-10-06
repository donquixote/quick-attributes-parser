<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Exception;

use Donquixote\QuickAttributes\Util\ParserUtilPhp7;
use Donquixote\QuickAttributes\Util\TokenPositionUtil;

class ParserException extends \Exception {

  /**
   * Prepends source file name to the message.
   *
   * @param string $file
   * @param string|null $basedir
   */
  public function setSourceFile(string $file, string $basedir = NULL): void {
    if ($basedir !== NULL && preg_match(
      '@^' . preg_quote($basedir  . '/', '@') . '(.*)$@',
      $file,
      $m)
    ) {
      $file = $m[1];
    }
    if (preg_match('@^Line (\d+\:\d+\: .*)$@', $this->message, $m)) {
      $this->message = 'In ' . $file . ':' . $m[1];
    }
    else {
      $this->message .= "\nParsed source file: $file.";
    }
  }

  /**
   * Static factory.
   *
   * @param array $tokens
   * @param int $pos
   * @param string $message
   * @param bool $append_found
   *
   * @return static
   *
   * @noinspection PhpMissingReturnTypeInspection
   */
  public static function fromTokenPos(array $tokens, int $pos, string $message, bool $append_found = TRUE) {
    [$line, $chrpos] = TokenPositionUtil::findLineChrPos($tokens, $pos);
    $message = "Line $line:$chrpos: $message";
    if ($append_found) {
      $message .= ' Found ' . ParserUtilPhp7::formatToken($tokens[$pos]) . '.';
    }
    return new static($message);
  }

}
