<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\ClassLike;

use Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilderInterface;
use Donquixote\QuickAttributes\Builder\ClassBody\ClassBodyBuilderInterface;

interface ClassLikeBuilderInterface {

  public function buildAttributes(): AttributesBuilderInterface;

  public function buildClassBody(): ClassBodyBuilderInterface;

}
