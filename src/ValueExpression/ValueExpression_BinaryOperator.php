<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\ValueExpression;

class ValueExpression_BinaryOperator implements ValueExpressionInterface {

  /**
   * @var \Donquixote\QuickAttributes\ValueExpression\ValueExpressionInterface
   */
  private ValueExpressionInterface $left;

  /**
   * @var \Donquixote\QuickAttributes\ValueExpression\ValueExpressionInterface
   */
  private ValueExpressionInterface $right;

  private string $operator;

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\ValueExpression\ValueExpressionInterface $left
   * @param \Donquixote\QuickAttributes\ValueExpression\ValueExpressionInterface $right
   * @param string $operator
   */
  public function __construct(ValueExpressionInterface $left, ValueExpressionInterface $right, string $operator) {
    $this->left = $left;
    $this->right = $right;
    $this->operator = $operator;
  }

  public function getValue() {
    /**
     * @noinspection PhpUnusedLocalVariableInspection
     * @psalm-suppress MixedAssignment
     */
    $left = $this->left->getValue();
    /**
     * @noinspection PhpUnusedLocalVariableInspection
     * @psalm-suppress MixedAssignment
     */
    $right = $this->right->getValue();
    return eval('return $left ' . $this->operator . ' $right;');
  }

  public function getVariabilityLevel(): int {
    return \max(
      $this->left->getVariabilityLevel(),
      $this->right->getVariabilityLevel());
  }

  public function __toString(): string {
    return '(' . $this->left . ' ' . $this->operator . ' ' . $this->right . ')';
  }

}
