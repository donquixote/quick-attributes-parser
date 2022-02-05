<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\Attributes;

use Donquixote\QuickAttributes\Builder\Arguments\ArgumentsBuilderInterface;

interface AttributesBuilderInterface {

  /**
   * @param class-string $name
   *
   * @return \Donquixote\QuickAttributes\Builder\Arguments\ArgumentsBuilderInterface
   */
  public function addAttribute(string $name): ArgumentsBuilderInterface;

}
