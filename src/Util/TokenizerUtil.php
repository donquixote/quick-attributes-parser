<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Util;

use Donquixote\QuickAttributes\Exception\TokenizerException;

class TokenizerUtil {

  /**
   * @param string $php
   * @param int $flags
   *
   * @return list<string|array{int, string, int}>
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public static function tokenGetAll(string $php, int $flags = 0): array {
    try {
      // set_error_handler() cannot intercept warnings from token_get_all().
      // So we need to use something else.
      \error_clear_last();
      $tokens = @\token_get_all($php, $flags);
    }
    catch (\ParseError $e) {
      throw TokenizerException::fromParseError($e);
    }
    $err = \error_get_last();
    if ($err !== null) {
      // @todo Enhance the exception message.
      throw TokenizerException::fromError($err);
    }
    return $tokens;
  }

}
