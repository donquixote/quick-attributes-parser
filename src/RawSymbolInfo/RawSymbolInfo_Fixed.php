<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\RawSymbolInfo;

class RawSymbolInfo_Fixed implements RawSymbolInfoInterface {

  /**
   * @var \Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface[]
   */
  private array $rawAttributes;

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface[] $rawAttributes
   */
  public function __construct(array $rawAttributes) {
    $this->rawAttributes = $rawAttributes;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributes(): array {
    return $this->rawAttributes;
  }

}
