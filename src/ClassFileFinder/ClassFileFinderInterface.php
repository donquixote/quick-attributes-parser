<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\ClassFileFinder;

interface ClassFileFinderInterface {

  /**
   * @param class-string $class
   *   Class name.
   *
   * @return string|null
   *   File that contains the class.
   */
  public function find(string $class): ?string;

}
