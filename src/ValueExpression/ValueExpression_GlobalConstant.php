<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\ValueExpression;

class ValueExpression_GlobalConstant implements ValueExpressionInterface {

  private string $name;

  /**
   * Constructor.
   *
   * @param string $name
   */
  private function __construct(string $name) {
    $this->name = $name;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return constant($this->name);
  }

  /**
   * {@inheritdoc}
   */
  public function getVariabilityLevel(): int {
    return self::VARIABILITY_CODE;
  }

}
