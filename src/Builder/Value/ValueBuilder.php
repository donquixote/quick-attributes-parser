<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\Value;

use Donquixote\QuickAttributes\ValueExpression\ValueExpression_Constant;
use Donquixote\QuickAttributes\ValueExpression\ValueExpression_Fixed;
use Donquixote\QuickAttributes\ValueExpression\ValueExpression_IdentityDecorator;
use Donquixote\QuickAttributes\ValueExpression\ValueExpression_Null;
use Donquixote\QuickAttributes\ValueExpression\ValueExpression_TernaryOperator;
use Donquixote\QuickAttributes\ValueExpression\ValueExpression_UnaryOperator;
use Donquixote\QuickAttributes\ValueExpression\ValueExpressionInterface;

class ValueBuilder implements ValueExpressionInterface, ValueBuilderInterface {

  private ValueExpressionInterface $value;

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\ValueExpression\ValueExpressionInterface $value
   */
  public function __construct(ValueExpressionInterface $value) {
    $this->value = $value;
  }

  public static function start(): self {
    return new self(new ValueExpression_Null());
  }

  /**
   * {@inheritdoc}
   */
  public function setFixedValue($value): self {
    $this->value = new ValueExpression_Fixed($value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setConstant(string $name, string $fallback = null): self {
    $this->value = new ValueExpression_Constant($name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function startArray(): ArrayBuilderInterface {
    return $this->value = new ArrayBuilder();
  }

  /**
   * {@inheritdoc}
   */
  public function appendBinaryOperator(string $operator): ValueBuilderInterface {
    if (!$this->value instanceof OperatorSoupBuilder) {
      $this->value = new OperatorSoupBuilder($this->value);
    }
    return $this->value->add($operator);
  }

  /**
   * {@inheritdoc}
   */
  public function appendArrayOffset(): ValueBuilderInterface {
    if (!$this->value instanceof OperatorSoupBuilder) {
      $this->value = new OperatorSoupBuilder($this->value);
    }
    return $this->value->addArrayOffset();
  }

  /**
   * {@inheritdoc}
   */
  public function close(): self {
    if ($this->value instanceof OperatorSoupBuilder) {
      $this->value = new ValueExpression_IdentityDecorator($this->value);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function startUnaryOperator(string $operator): ValueBuilderInterface {
    $operand = self::start();
    $this->value = new ValueExpression_UnaryOperator($operator, $operand);
    return $operand;
  }

  /**
   * {@inheritdoc}
   */
  public function appendTernaryOperator(): array {
    $valueIfTrue = self::start();
    $valueIfFalse = self::start();
    $this->value = new ValueExpression_TernaryOperator($this->value, $valueIfTrue, $valueIfFalse);
    return [$valueIfTrue, $valueIfFalse];
  }

  /**
   * {@inheritdoc}
   */
  public function applyUnaryOperator(string $operator): void {
    $this->value = new ValueExpression_UnaryOperator($operator, $this->value);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString(): string {
    return $this->value->__toString();
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return $this->value->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function getVariabilityLevel(): int {
    return $this->value->getVariabilityLevel();
  }

}
