<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\ValueExpression;

class ValueExpression_ClassConstant implements ValueExpressionInterface {

  private string $class;

  private string $name;

  /**
   * Constructor.
   *
   * @param string $class
   * @param string $name
   */
  public function __construct(string $class, string $name) {
    $this->class = $class;
    $this->name = $name;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return ($this->class)::{$this->name};
  }

  /**
   * {@inheritdoc}
   */
  public function getVariabilityLevel(): int {
    $rc = new \ReflectionClass($this->class);
    return $rc->isUserDefined()
      ? self::VARIABILITY_CODE
      : self::VARIABILITY_SERVER;
  }

}
