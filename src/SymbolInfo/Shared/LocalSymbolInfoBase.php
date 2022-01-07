<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\Shared;

abstract class LocalSymbolInfoBase extends SymbolInfoBase {

  private string $name;

  /**
   * Constructor.
   *
   * @param string $name
   * @param list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface> $attributes
   */
  public function __construct(string $name, array $attributes) {
    parent::__construct($attributes);
    $this->name = $name;
  }

  public function getName(): string {
    return $this->name;
  }

}
