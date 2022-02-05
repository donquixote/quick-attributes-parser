<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\Parameters;

use Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilder;
use Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilderInterface;

class ParametersBuilder_NoOp implements ParametersBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public function addParameter(string $name): AttributesBuilderInterface {
    return new AttributesBuilder();
  }

  /**
   * {@inheritdoc}
   */
  public function markAsComplete(): void {
    // Do nothing.
  }

}
