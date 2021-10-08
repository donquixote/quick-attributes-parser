<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\ValueExpression;

interface ValueExpressionInterface {

  const VARIABILITY_ZERO = 0;

  const VARIABILITY_SERVER = 1;

  const VARIABILITY_CODE = 2;

  const VARIABILITY_RUNTIME = 3;

  /**
   * @return mixed
   *
   * @throws \ReflectionException
   */
  public function getValue();

  /**
   * @return int
   *
   * @throws \ReflectionException
   */
  public function getVariabilityLevel(): int;

}
