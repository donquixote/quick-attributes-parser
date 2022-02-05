<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\ClassBody;

use Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilder;
use Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilderInterface;
use Donquixote\QuickAttributes\Builder\FunctionLike\FunctionLikeBuilder;
use Donquixote\QuickAttributes\Builder\FunctionLike\FunctionLikeBuilderInterface;
use Donquixote\QuickAttributes\Builder\Parameters\ParametersBuilder_NoOp;

class ClassBodyBuilder_NoOp implements ClassBodyBuilderInterface {

  public function addProperty(string $name): AttributesBuilderInterface {
    // Do nothing.
    return new AttributesBuilder();
  }

  public function addConstant(string $name): AttributesBuilderInterface {
    // Do nothing.
    return new AttributesBuilder();
  }

  public function addMethod(string $name): FunctionLikeBuilderInterface {
    return new FunctionLikeBuilder(
      new AttributesBuilder(),
      new ParametersBuilder_NoOp());
  }

  public function markAsComplete(): void {
    // Do nothing.
  }

}
