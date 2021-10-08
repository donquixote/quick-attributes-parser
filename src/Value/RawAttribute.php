<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Value;

use Donquixote\QuickAttributes\ValueExpression\ValueExpressionInterface;

class RawAttribute {

  private string $name;

  /**
   * @var \Donquixote\QuickAttributes\ValueExpression\ValueExpressionInterface|null
   */
  private ?ValueExpressionInterface $valueExpression;

  /**
   * Constructor.
   *
   * @param string $name
   * @param \Donquixote\QuickAttributes\ValueExpression\ValueExpressionInterface|null $valueExpression
   */
  public function __construct(string $name, ?ValueExpressionInterface $valueExpression) {
    $this->name = $name;
    $this->valueExpression = $valueExpression;
  }

  /**
   * @return string
   */
  public function getName(): string {
    return $this->name;
  }

  public function isA(string $type): bool {
    return is_a($this->name, $type, TRUE);
  }

  /**
   * @return array
   *
   * @throws \ReflectionException
   */
  public function getArgs(): array {
    return ($this->valueExpression !== NULL)
      ? $this->valueExpression->getValue()
      : [];
  }

}
