<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests\Fixture;

final class SU {

  /**
   * @param string $search
   * @param string $delim
   * @param string[] $replacements
   *
   * @return string
   */
  public static function regex(string $search, string $delim, array $replacements = []): string {
    $regex = preg_quote($search, $delim);
    $regex = strtr($regex, $replacements);
    return $delim . $regex . $delim;
  }

}
