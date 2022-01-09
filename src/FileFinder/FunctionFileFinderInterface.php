<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\FileFinder;

interface FunctionFileFinderInterface {

  /**
   * @param callable-string $function
   *   Function name.
   *
   * @return string|null
   *   File that defines the function, or NULL if not found.
   */
  public function find(string $function): ?string;

}
