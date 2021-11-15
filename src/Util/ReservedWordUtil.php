<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Util;

class ReservedWordUtil {

  /**
   * @var array<string, bool>
   */
  private static $map = [];

  /**
   * @param string $word
   *
   * @return bool
   */
  public static function validMemberName(string $word): bool {
    return self::$map[$word]
      ??= self::calc($word);
  }

  /**
   * @param string $word
   *
   * @return bool
   */
  private static function calc(string $word): bool {
    if (!\preg_match('@^[a-z][a-z0-9_]*$@i', $word)) {
      return false;
    }
    try {
      $tokens = \token_get_all(
        "<?php class C {const $word = 5;}",
        \TOKEN_PARSE);
      return $tokens[8][0] === \T_STRING;
    }
    catch (\Throwable $e) {
      return false;
    }
  }

}
