<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\ValueExpression;

/**
 * Global constant or class constant.
 */
class ValueExpression_ConstantWithFallback implements ValueExpressionInterface {

  private string $name;

  private string $fallback;

  /**
   * Constructor.
   *
   * @param string $name
   *   One of:
   *   - Qualified name of a global constant.
   *   - Qualified class name + '::' + class constant name.
   * @param string $fallback
   *   Fallback constant name, if $name is not defined.
   */
  public function __construct(string $name, string $fallback) {
    $this->name = $name;
    $this->fallback = $fallback;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    return \defined($this->name)
      ? \constant($this->name)
      : \constant($this->fallback);
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
    return \sprintf(
      '(\\defined(%s) ? \\%s : \\%s)',
      \var_export($this->name, true),
      $this->name,
      $this->fallback);
  }

}
