<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\ValueExpression;

class ValueExpression_Array implements ValueExpressionInterface {

  /**
   * @var \Donquixote\QuickAttributes\ValueExpression\ValueExpressionInterface[]
   */
  private array $items;

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\ValueExpression\ValueExpressionInterface[] $items
   */
  public function __construct(array $items) {
    $this->items = $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $value = [];
    foreach ($this->items as $k => $item) {
      $value[$k] = $item->getValue();
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getVariabilityLevel(): int {
    $levels = [];
    if (!$this->items) {
      return self::VARIABILITY_ZERO;
    }
    foreach ($this->items as $item) {
      $levels[] = $item->getVariabilityLevel();
    }
    return max(...$levels);
  }

}
