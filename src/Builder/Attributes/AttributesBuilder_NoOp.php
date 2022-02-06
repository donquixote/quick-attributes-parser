<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\Attributes;

use Donquixote\QuickAttributes\Builder\Arguments\ArgumentsBuilder_NoOp;
use Donquixote\QuickAttributes\Builder\Arguments\ArgumentsBuilderInterface;

class AttributesBuilder_NoOp implements AttributesBuilderInterface {

  public function addAttribute(string $name): ArgumentsBuilderInterface {
    return new ArgumentsBuilder_NoOp();
  }

}
