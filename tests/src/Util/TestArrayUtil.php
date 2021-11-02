<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests\Util;

class TestArrayUtil {

  /**
   * @param array $data
   * @param list<string> $keys
   */
  public static function normalizeKeys(array &$data, array $keys): void {
    $normalized = [];
    foreach ($keys as $key) {
      if (isset($data[$key])) {
        /** @psalm-suppress MixedAssignment */
        $normalized[$key] = $data[$key];
      }
    }
    $normalized += $data;
    $data = $normalized;
  }

}
