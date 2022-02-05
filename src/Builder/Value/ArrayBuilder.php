<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\Value;

use Donquixote\QuickAttributes\ValueExpression\ValueExpressionInterface;

class ArrayBuilder implements ValueExpressionInterface, ArrayBuilderInterface {

  /**
   * @var list<array{array-key|null|ValueExpressionInterface, ValueExpressionInterface}>
   */
  private array $items = [];

  /**
   * {@inheritdoc}
   */
  public function add($key = null): ValueBuilderInterface {
    $value = ValueBuilder::start();
    $this->items[] = [$key, $value];
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function addKeyValue(): array {
    return $this->items[] = [ValueBuilder::start(), ValueBuilder::start()];
  }

  /**
   * {@inheritdoc}
   */
  public function mapTo(): ValueBuilderInterface {
    $i = \count($this->items) - 1;
    $item =& $this->items[$i];
    if ($item[0] !== null) {
      throw new \BadFunctionCallException('There is already an array key.');
    }
    $item[0] = $item[1];
    return $item[1] = ValueBuilder::start();
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(): array {
    /** @var mixed[] $result */
    $result = [];
    foreach ($this->items as [$key, $value]) {
      if ($key instanceof ValueExpressionInterface) {
        /** @psalm-suppress MixedAssignment */
        $key = $key->getValue();
        if (!\is_string($key) && !\is_int($key)) {
          throw new \ReflectionException('Invalid array key.');
        }
      }
      if ($key === NULL) {
        /** @psalm-var mixed */
        $result[] = $value->getValue();
      }
      else {
        /** @psalm-suppress MixedAssignment */
        $result[$key] = $value->getValue();
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getVariabilityLevel(): int {
    $levels = [];
    if (!$this->items) {
      return self::VARIABILITY_ZERO;
    }
    foreach ($this->items as [$key, $value]) {
      if ($key instanceof ValueExpressionInterface) {
        $levels[] = $key->getVariabilityLevel();
      }
      $levels[] = $value->getVariabilityLevel();
    }
    return \max(...$levels);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString(): string {
    if ($this->items === []) {
      return '[]';
    }
    $parts = [];
    foreach ($this->items as [$key, $value]) {
      if ($key instanceof ValueExpressionInterface) {
        $keyPhp = (string) $key;
      }
      else {
        $keyPhp = \var_export($key, true);
      }
      if ($keyPhp === 'NULL' || $keyPhp === 'null') {
        $parts[] = (string) $value;
      }
      else {
        $parts[] = $keyPhp . ' => ' . $value;
      }
    }
    return '[' . \implode(', ', $parts) . ']';
  }

}
