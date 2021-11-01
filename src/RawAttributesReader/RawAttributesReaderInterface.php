<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\RawAttributesReader;

use Donquixote\QuickAttributes\Value\SymbolHandle;

interface RawAttributesReaderInterface {

  /**
   * @param \Donquixote\QuickAttributes\Value\SymbolHandle $symbol
   *
   * @return list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface>
   *
   * @throws \ReflectionException
   */
  public function read(SymbolHandle $symbol): array;

}
