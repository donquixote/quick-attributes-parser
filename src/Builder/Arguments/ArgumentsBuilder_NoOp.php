<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\Arguments;

use Donquixote\QuickAttributes\Builder\Value\ValueBuilder_NoOp;
use Donquixote\QuickAttributes\Builder\Value\ValueBuilderInterface;

class ArgumentsBuilder_NoOp implements ArgumentsBuilderInterface {

  public function addArgument(string $key = null): ValueBuilderInterface {
    return new ValueBuilder_NoOp();
  }

}
