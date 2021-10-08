<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\AttributesList;

use Donquixote\QuickAttributes\Util\ArgumentsUtil;

class AttributesList_Fallback implements AttributesListInterface {

  /**
   * @var \Donquixote\QuickAttributes\Value\RawAttribute[]
   */
  private array $rawAttributes;

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\Value\RawAttribute[] $rawAttributes
   */
  public function __construct(array $rawAttributes) {
    $this->rawAttributes = $rawAttributes;
  }

  public function has(string $type = NULL): bool {
    if ($type === NULL) {
      return $this->rawAttributes !== [];
    }
    foreach ($this->rawAttributes as $rawAttribute) {
      if ($rawAttribute->isA($type)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  public function count(string $type = NULL): int {
    if ($type === NULL) {
      return count($this->rawAttributes);
    }
    $n = 0;
    foreach ($this->rawAttributes as $rawAttribute) {
      if ($rawAttribute->isA($type)) {
        ++$n;
      }
    }
    return $n;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstances(string $type = NULL): array {
    $instances = [];
    foreach ($this->rawAttributes as $i => $rawAttribute) {
      $class = $rawAttribute->getName();
      if ($type === NULL
        || is_a($class, $type, TRUE)
      ) {
        $args = $rawAttribute->getArgs();
        $mappedArgs = ArgumentsUtil::classMapArgs(
          $class = $rawAttribute->getName(),
          $args);
        assert(count($args) === count($mappedArgs));
        $instances[$i] = new $class(...$mappedArgs);
      }
    }
    return $instances;
  }

}
