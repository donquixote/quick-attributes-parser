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
  public function getValue(): array {
    $value = [];
    foreach ($this->items as $k => $item) {
      /** @psalm-suppress MixedAssignment */
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
    return \max(...$levels);
  }

  public function __toString(): string {
    if ($this->items === []) {
      return '[]';
    }
    $php = '';
    if (\array_values($this->items) === $this->items) {
      foreach ($this->items as $item) {
        $php .= '  ' . $item . ",\n";
      }
    }
    else {
      foreach ($this->items as $k => $item) {
        $php .= '  ' . \var_export($k, true) . ' => ' . $item . ",\n";
      }
    }
    return "[\n$php]";
  }

}
