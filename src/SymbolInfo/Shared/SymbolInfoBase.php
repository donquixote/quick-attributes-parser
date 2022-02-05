<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\Shared;

abstract class SymbolInfoBase implements SymbolInfoInterface {

  use AttributesInfoDecoratorTrait;

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\SymbolInfo\Shared\AttributesInfoInterface $attributes
   */
  public function __construct(AttributesInfoInterface $attributes) {
    $this->attributes = $attributes;
  }

}
