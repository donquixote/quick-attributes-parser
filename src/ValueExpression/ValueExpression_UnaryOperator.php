<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\ValueExpression;

class ValueExpression_UnaryOperator implements ValueExpressionInterface {

  private string $operator;

  /**
   * @var \Donquixote\QuickAttributes\ValueExpression\ValueExpressionInterface
   */
  private ValueExpressionInterface $operand;

  /**
   * Constructor.
   *
   * @param string $operator
   * @param \Donquixote\QuickAttributes\ValueExpression\ValueExpressionInterface $operand
   */
  public function __construct(string $operator, ValueExpressionInterface $operand) {
    $this->operator = $operator;
    $this->operand = $operand;
  }

  public function getValue() {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $_ = $this->operand->getValue();
    return eval('return ' . $this->operator . '$_;');
  }

  public function getVariabilityLevel(): int {
    return $this->operand->getVariabilityLevel();
  }

  public function __toString(): string {
    return $this->operator . $this->operand;
  }

}
