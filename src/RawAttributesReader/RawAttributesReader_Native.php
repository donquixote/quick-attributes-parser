<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\RawAttributesReader;

use Donquixote\QuickAttributes\RawAttribute\RawAttribute_NativeReflection;
use Donquixote\QuickAttributes\Value\SymbolHandle;

class RawAttributesReader_Native implements RawAttributesReaderInterface {

  /**
   * {@inheritdoc}
   */
  public function read(SymbolHandle $symbol): array {
    $rawAttributes = [];
    /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
    foreach ($symbol->reflect()->getAttributes() as $reflectionAttribute) {
      $rawAttributes[] = new RawAttribute_NativeReflection($reflectionAttribute);
    }
    return $rawAttributes;
  }

}
