<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\ValueExpression;

class ValueExpression_Null implements ValueExpressionInterface {

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return null;
  }

  /**
   * {@inheritdoc}
   */
  public function getVariabilityLevel(): int {
    return self::VARIABILITY_ZERO;
  }

  public function __toString(): string {
    return 'null';
  }

}
