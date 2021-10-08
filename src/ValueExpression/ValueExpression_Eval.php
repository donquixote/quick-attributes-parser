<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\ValueExpression;

class ValueExpression_Eval implements ValueExpressionInterface {

  private string $php;

  /**
   * Constructor.
   *
   * @param string $php
   */
  public function __construct(string $php) {
    $this->php = $php;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return eval($this->php);
  }

  /**
   * {@inheritdoc}
   */
  public function getVariabilityLevel(): int {
    return self::VARIABILITY_RUNTIME;
  }

}
