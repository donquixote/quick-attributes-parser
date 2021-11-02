<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\RawSymbolInfo;

use Donquixote\QuickAttributes\RawAttribute\RawAttribute_NativeReflection;
use Donquixote\QuickAttributes\Value\SymbolHandle;

class RawSymbolInfo_Native implements RawSymbolInfoInterface {

  /**
   * @var \Donquixote\QuickAttributes\Value\SymbolHandle
   */
  private SymbolHandle $handle;

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\Value\SymbolHandle $handle
   */
  public function __construct(SymbolHandle $handle) {
    $this->handle = $handle;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributes(): array {
    $rawAttributes = [];
    /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
    foreach ($this->handle->reflect()->getAttributes() as $ra) {
      $rawAttributes[] = new RawAttribute_NativeReflection($ra);
    }
    return $rawAttributes;
  }

}
