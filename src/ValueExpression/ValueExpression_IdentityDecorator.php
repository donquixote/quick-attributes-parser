<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\ValueExpression;

/**
 * Global constant or class constant.
 */
class ValueExpression_IdentityDecorator implements ValueExpressionInterface {

  /**
   * @var \Donquixote\QuickAttributes\ValueExpression\ValueExpressionInterface
   */
  private ValueExpressionInterface $decorated;

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\ValueExpression\ValueExpressionInterface $decorated
   */
  public function __construct(ValueExpressionInterface $decorated) {
    $this->decorated = $decorated;
  }

  public function getValue() {
    return $this->decorated->getValue();
  }

  public function getVariabilityLevel(): int {
    return $this->decorated->getVariabilityLevel();
  }

  public function __toString(): string {
    return $this->decorated->__toString();
  }

}
