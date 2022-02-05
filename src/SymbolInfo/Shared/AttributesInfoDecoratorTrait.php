<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\Shared;

trait AttributesInfoDecoratorTrait {

  private AttributesInfoInterface $attributes;

  /**
   * @return list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface>
   */
  public function getAttributes(): array {
    return $this->attributes->getAttributes();
  }

}
