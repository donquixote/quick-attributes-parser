<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\Value;

use Donquixote\QuickAttributes\ValueExpression\ValueExpressionInterface;

class OperatorSoupBuilder implements ValueExpressionInterface, OperatorSoupBuilderInterface {

  /**
   * @var list<ValueExpressionInterface>
   */
  private array $operands = [];

  /**
   * PHP value expression with operators and variables.
   *
   * E.g. `$_[0] + $_[1] - $_[2]`.
   *
   * @var string
   */
  private string $php;

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\ValueExpression\ValueExpressionInterface $first
   */
  public function __construct(ValueExpressionInterface $first) {
    $this->operands[] = $first;
    $this->php = '$_[0]';
  }

  /**
   * {@inheritdoc}
   */
  public function add(string $operator): ValueBuilderInterface {
    $this->php .= ' ' . $operator . ' $_[' . \count($this->operands) . ']';
    return $this->operands[] = ValueBuilder::start();
  }

  /**
   * {@inheritdoc}
   */
  public function addArrayOffset(): ValueBuilderInterface {
    $this->php .= '[$_[' . \count($this->operands) . ']]';
    return $this->operands[] = ValueBuilder::start();
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $_ = [];
    foreach ($this->operands as $part) {
      /** @psalm-suppress MixedAssignment */
      $_[] = $part->getValue();
    }
    /** @psalm-suppress RedundantCondition */
    \assert(\is_array($_));
    try {
      return eval('return ' . $this->php . ';');
    }
    catch (\Throwable $e) {
      /** @psalm-suppress MixedArgument */
      throw new \ReflectionException($e->getMessage(), 0, $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getVariabilityLevel(): int {
    $levels = [];
    if (!$this->operands) {
      return self::VARIABILITY_ZERO;
    }
    foreach ($this->operands as $part) {
      $levels[] = $part->getVariabilityLevel();
    }
    return \max(...$levels);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString(): string {
    if ($this->operands === []) {
      return '[]';
    }
    $replacements = [];
    foreach ($this->operands as $i => $operand) {
      $replacements['$_[' . $i . ']'] = (string) $operand;
    }
    return '(' . \strtr($this->php, $replacements) . ')';
  }

}
