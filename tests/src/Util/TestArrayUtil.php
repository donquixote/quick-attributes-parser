<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests\Util;

class TestArrayUtil {

  /**
   * @param array $data
   * @param list<string> $keys
   */
  public static function normalizeKeys(array &$data, array $keys): void {
    $keymap = \array_fill_keys($keys, true);
    $keymap = \array_intersect_key($keymap, $data);
    $data = \array_replace($keymap, $data);
  }

}
