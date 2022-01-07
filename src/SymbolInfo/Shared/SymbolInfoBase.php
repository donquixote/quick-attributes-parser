<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\Shared;

abstract class SymbolInfoBase implements SymbolInfoInterface {

  /**
   * @var list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface>
   */
  private array $attributes;

  /**
   * Constructor.
   *
   * @param list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface> $attributes
   */
  public function __construct(array $attributes) {
    $this->attributes = $attributes;
  }

  public function getAttributes(): array {
    return $this->attributes;
  }

}
