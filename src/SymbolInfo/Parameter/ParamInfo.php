<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\Parameter;

class ParamInfo implements ParamInfoInterface {

  private string $name;

  /**
   * @var list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface>
   */
  private array $attributes;

  /**
   * Constructor.
   *
   * @param string $name
   * @param list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface> $attributes
   */
  public function __construct(string $name, array $attributes) {
    $this->name = $name;
    $this->attributes = $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributes(): array {
    return $this->attributes;
  }

}
