<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Lookup;

class Lookup_LazyLoadDecorator implements LookupInterface {

  /**
   * @var \Donquixote\QuickAttributes\Lookup\LookupInterface
   */
  private LookupInterface $lookup;

  private \Iterator $it;

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\Lookup\LookupInterface $lookup
   * @param \Iterator $it
   */
  public function __construct(LookupInterface $lookup, \Iterator $it) {
    $this->lookup = $lookup;
    $this->it = $it;
  }

  public function keyGetImports(string $key): ?array {
    if (null !== $imports = $this->lookup->keyGetImports($key)) {
      return $imports;
    }
    for (; $this->it->valid(); $this->it->next()) {
      if (null !== $imports = $this->lookup->keyGetImports($key)) {
        return $imports;
      }
    }
    return null;
  }

  public function keyGetAttributes(string $key): ?array {
    if (null !== $imports = $this->lookup->keyGetAttributes($key)) {
      return $imports;
    }
    for (; $this->it->valid(); $this->it->next()) {
      if (null !== $imports = $this->lookup->keyGetAttributes($key)) {
        return $imports;
      }
    }
    return null;
  }

  public function readToplevelNames(int &$offset = 0): \Iterator {
    // Start with known names.
    yield from $this->lookup->readToplevelNames($offset);
    for (; $this->it->valid(); $this->it->next()) {
      yield from $this->lookup->readToplevelNames($offset);
    }
  }

  public function keyReadChildNames(string $key, int &$offset = 0): \Iterator {
    // Start with known names.
    yield from $this->lookup->keyReadChildNames($key, $offset);
    for (; $offset !== -1 && $this->it->valid(); $this->it->next()) {
      yield from $this->lookup->keyReadChildNames($key, $offset);
    }
  }

}
