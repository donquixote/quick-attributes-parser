<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\Shared;

interface AttributesInfoInterface {

  /**
   * @return list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface>
   */
  public function getAttributes(): array;

}
