<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\ValueExpression;

class ValueExpression_Fixed implements ValueExpressionInterface {

  private $value;

  /**
   * Constructor.
   *
   * @param mixed $value
   */
  public function __construct($value) {
    $this->value = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getVariabilityLevel(): int {
    return self::VARIABILITY_ZERO;
  }

}
