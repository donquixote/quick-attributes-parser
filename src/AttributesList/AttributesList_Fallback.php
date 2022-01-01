<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\AttributesList;

use Donquixote\QuickAttributes\RawAttribute\RawAttribute;

class AttributesList_Fallback implements AttributesListInterface {

  /**
   * @var \Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface<object>[]
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
  public function has(string $type = null): bool {
    if ($type === null) {
      return $this->rawAttributes !== [];
    }
    foreach ($this->rawAttributes as $rawAttribute) {
      if (\is_a($rawAttribute->getName(), $type, true)) {
        return true;
      }
    }
    return false;
  }

  /**
   * {@inheritdoc}
   */
  public function count(string $type = null): int {
    if ($type === null) {
      return \count($this->rawAttributes);
    }
    $n = 0;
    foreach ($this->rawAttributes as $rawAttribute) {
      if (\is_a($rawAttribute->getName(), $type, true)) {
        ++$n;
      }
    }
    return $n;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstances(string $type = null): array {
    return RawAttribute::createInstances($this->rawAttributes, $type);
  }

}
