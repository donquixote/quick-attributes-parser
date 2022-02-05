<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\File;

use Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilder;
use Donquixote\QuickAttributes\Builder\ClassBody\ClassBodyBuilder_NoOp;
use Donquixote\QuickAttributes\Builder\ClassLike\ClassLikeBuilder;
use Donquixote\QuickAttributes\Builder\ClassLike\ClassLikeBuilderInterface;
use Donquixote\QuickAttributes\Builder\FunctionLike\FunctionLikeBuilder;
use Donquixote\QuickAttributes\Builder\FunctionLike\FunctionLikeBuilderInterface;
use Donquixote\QuickAttributes\Builder\Parameters\ParametersBuilder_NoOp;

class FileBuilder_NoOp implements FileBuilderInterface {

  public function addClass(string $name, array $imports): ClassLikeBuilderInterface {
    return new ClassLikeBuilder(
      new AttributesBuilder(),
    new ClassBodyBuilder_NoOp());
  }

  public function addFunction(string $name, array $imports): FunctionLikeBuilderInterface {
    return new FunctionLikeBuilder(
      new AttributesBuilder(),
      new ParametersBuilder_NoOp());
  }

}
