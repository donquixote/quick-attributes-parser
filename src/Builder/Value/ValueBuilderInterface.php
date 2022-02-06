<?php

/**
 * @file
 */

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\Value;

interface ValueBuilderInterface {

  /**
   * @param mixed $value
   *
   * @return $this
   */
  public function setFixedValue($value): self;

  /**
   * @param string $name
   *   Qualified name.
   * @param string|null $fallback
   *   Fallback constant name, if $name is not defined.
   *
   * @return $this
   */
  public function setConstant(string $name, string $fallback = null): self;

  /**
   * @return ArrayBuilderInterface
   */
  public function startArray(): ArrayBuilderInterface;

  /**
   * @param string $operator
   *
   * @return self
   *   Right operand.
   */
  public function appendBinaryOperator(string $operator): self;


  /**
   * @return self
   *   Array or string offset.
   */
  public function appendArrayOffset(): self;

  /**
   * Closes the current operator soup.
   *
   * @return $this
   */
  public function close(): self;

  /**
   * @param string $operator
   *
   * @return self
   */
  public function startUnaryOperator(string $operator): self;

  /**
   * @return array{self, self}
   */
  public function appendTernaryOperator(): array;

  /**
   * @param string $operator
   *
   * @return mixed
   */
  public function applyUnaryOperator(string $operator): void;

}
