<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\AttributeReader;

class AttributeReader {

  public static function create(): AttributeReaderInterface {
    return \PHP_VERSION_ID < 80000
      ? AttributeReader_Fallback::create()
      : new AttributeReader_Native();
  }

}
