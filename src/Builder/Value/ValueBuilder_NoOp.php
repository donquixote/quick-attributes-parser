<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\Value;

class ValueBuilder_NoOp implements ValueBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public function setFixedValue($value): ValueBuilderInterface {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setConstant(string $name, string $fallback = null): ValueBuilderInterface {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function startArray(): ArrayBuilderInterface {
    return new ArrayBuilder_NoOp();
  }

  /**
   * {@inheritdoc}
   */
  public function appendBinaryOperator(string $operator): ValueBuilderInterface {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function appendArrayOffset(): ValueBuilderInterface {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function close(): ValueBuilderInterface {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function startUnaryOperator(string $operator): ValueBuilderInterface {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function appendTernaryOperator(): array {
    return [$this, $this];
  }

  /**
   * {@inheritdoc}
   */
  public function applyUnaryOperator(string $operator): void {
    // Do nothing.
  }

}
