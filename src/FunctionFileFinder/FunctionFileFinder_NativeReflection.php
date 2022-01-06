<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\FunctionFileFinder;

class FunctionFileFinder_NativeReflection implements FunctionFileFinderInterface {

  /**
   * {@inheritdoc}
   */
  public function find(string $function): ?string {
    try {
      $rf = new \ReflectionFunction($function);
    }
    catch (\ReflectionException $e) {
      return null;
    }
    return $rf->getFileName();
  }

}
