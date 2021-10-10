<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\AttributeReader;

use Donquixote\QuickAttributes\AttributesList\AttributesList_Native;
use Donquixote\QuickAttributes\AttributesList\AttributesListInterface;
use Donquixote\QuickAttributes\Value\SymbolHandle;

class AttributeReader_Native implements AttributeReaderInterface {

  public function __construct() {
    if (PHP_VERSION_ID < 80000) {
      throw new \RuntimeException('Can only use this class in PHP 8+.');
    }
  }

  public function read(SymbolHandle $symbol): ?AttributesListInterface {
    return new AttributesList_Native($symbol->reflect());
  }

}
