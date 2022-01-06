<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Lookup;

interface LookupInterface {

  /**
   * @param string $key
   *
   * @return array<string, string>|null
   */
  public function keyGetImports(string $key): ?array;

  /**
   * @param string $key
   *
   * @return list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface>|null
   */
  public function keyGetAttributes(string $key): ?array;

  /**
   * @param int $offset
   *   Before: Offset to start from.
   *   After: Offset to continue on next call.
   *
   * @return \Iterator<int, string>
   */
  public function readToplevelNames(int &$offset = 0): \Iterator;

  /**
   * @param string $key
   * @param int $offset
   *   Before: Offset to start from.
   *   After: Offset to continue on next call, OR -1 if finished.
   *
   * @return \Iterator<int, string>
   */
  public function keyReadChildNames(string $key, int &$offset = 0): \Iterator;

}
