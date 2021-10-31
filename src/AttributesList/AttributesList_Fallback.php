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
  public function has(string $type = NULL): bool {
    if ($type === NULL) {
      return $this->rawAttributes !== [];
    }
    foreach ($this->rawAttributes as $rawAttribute) {
      if (is_a($rawAttribute->getName(), $type, TRUE)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function count(string $type = NULL): int {
    if ($type === NULL) {
      return count($this->rawAttributes);
    }
    $n = 0;
    foreach ($this->rawAttributes as $rawAttribute) {
      if (is_a($rawAttribute->getName(), $type, TRUE)) {
        ++$n;
      }
    }
    return $n;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstances(string $type = NULL): array {
    return RawAttribute::createInstances($this->rawAttributes, $type);
  }

}
