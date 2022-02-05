<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\Parameters;

use Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilderInterface;

interface ParametersBuilderInterface {

  /**
   * @param string $name
   */
  public function addParameter(string $name): AttributesBuilderInterface;

  /**
   * Marks the parameter list as complete.
   */
  public function markAsComplete(): void;

}
