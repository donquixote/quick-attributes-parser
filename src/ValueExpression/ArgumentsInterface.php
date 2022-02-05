<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\ValueExpression;

interface ArgumentsInterface {

  /**
   * Gets argument values.
   *
   * @return array
   *
   * @throws \ReflectionException
   */
  public function getArguments(): array;

}
