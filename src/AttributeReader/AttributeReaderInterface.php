<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\AttributeReader;

use Donquixote\QuickAttributes\AttributesList\AttributesListInterface;
use Donquixote\QuickAttributes\Value\SymbolHandle;

interface AttributeReaderInterface {

  /**
   * @param \Donquixote\QuickAttributes\Value\SymbolHandle $symbol
   *
   * @return \Donquixote\QuickAttributes\AttributesList\AttributesListInterface|null
   *
   * @throws \ReflectionException
   */
  public function read(SymbolHandle $symbol): ?AttributesListInterface;

}
