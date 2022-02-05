<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\Arguments;

use Donquixote\QuickAttributes\Builder\Value\ValueBuilderInterface;

interface ArgumentsBuilderInterface {

  /**
   * @param string|null $key
   *
   * @return \Donquixote\QuickAttributes\Builder\Value\ValueBuilderInterface
   */
  public function addArgument(string $key = null): ValueBuilderInterface;

}
