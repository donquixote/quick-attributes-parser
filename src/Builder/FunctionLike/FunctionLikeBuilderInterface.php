<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\FunctionLike;

use Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilderInterface;
use Donquixote\QuickAttributes\Builder\Parameters\ParametersBuilderInterface;

interface FunctionLikeBuilderInterface {

  public function buildAttributes(): AttributesBuilderInterface;

  public function buildParameters(): ParametersBuilderInterface;

}
