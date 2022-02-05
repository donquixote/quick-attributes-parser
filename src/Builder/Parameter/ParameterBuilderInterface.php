<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\Parameter;

use Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilderInterface;

interface ParameterBuilderInterface {

  public function buildAttributes(): AttributesBuilderInterface;

}
