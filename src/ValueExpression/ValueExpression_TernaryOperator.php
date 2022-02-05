<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\ValueExpression;

class ValueExpression_TernaryOperator implements ValueExpressionInterface {

  /**
   * @var \Donquixote\QuickAttributes\ValueExpression\ValueExpressionInterface
   */
  private ValueExpressionInterface $condition;

  /**
   * @var \Donquixote\QuickAttributes\ValueExpression\ValueExpressionInterface
   */
  private ValueExpressionInterface $valueIfTrue;

  /**
   * @var \Donquixote\QuickAttributes\ValueExpression\ValueExpressionInterface
   */
  private ValueExpressionInterface $valueIfFalse;

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\ValueExpression\ValueExpressionInterface $condition
   * @param \Donquixote\QuickAttributes\ValueExpression\ValueExpressionInterface $valueIfTrue
   * @param \Donquixote\QuickAttributes\ValueExpression\ValueExpressionInterface $valueIfFalse
   */
  public function __construct(ValueExpressionInterface $condition, ValueExpressionInterface $valueIfTrue, ValueExpressionInterface $valueIfFalse) {
    $this->condition = $condition;
    $this->valueIfTrue = $valueIfTrue;
    $this->valueIfFalse = $valueIfFalse;
  }

  public function getValue() {
    return $this->condition->getValue()
      ? $this->valueIfTrue->getValue()
      : $this->valueIfFalse->getValue();
  }

  public function getVariabilityLevel(): int {
    return \max(
      $this->condition->getVariabilityLevel(),
      $this->valueIfTrue->getVariabilityLevel(),
      $this->valueIfFalse->getVariabilityLevel());
  }

  public function __toString(): string {
    return '(' . $this->condition . ' ? ' . $this->valueIfTrue . ' : ' . $this->valueIfFalse . ')';
  }

}
