<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\RawAttributesReader;

class RawAttributesReader {

  /**
   * @return \Donquixote\QuickAttributes\RawAttributesReader\RawAttributesReaderInterface
   */
  public static function create(): RawAttributesReaderInterface {
    return PHP_VERSION_ID < 80000
      ? RawAttributesReader_Fallback::create()
      : new RawAttributesReader_Native();
  }

}
