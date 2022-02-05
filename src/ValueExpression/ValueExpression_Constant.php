<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\ValueExpression;

/**
 * Global constant or class constant.
 */
class ValueExpression_Constant implements ValueExpressionInterface {

  private string $name;

  /**
   * Constructor.
   *
   * @param string $name
   *   One of:
   *   - Qualified name of a global constant.
   *   - Qualified class name + '::' + class constant name.
   */
  public function __construct(string $name) {
    $this->name = $name;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return \constant($this->name);
  }

  /**
   * {@inheritdoc}
   */
  public function getVariabilityLevel(): int {
    return self::VARIABILITY_CODE;
  }

  /**
   * {@inheritdoc}
   */
  public function __toString(): string {
    return '\\' . $this->name;
  }

}
