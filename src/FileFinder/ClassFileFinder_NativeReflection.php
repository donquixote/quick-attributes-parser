<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\FileFinder;

class ClassFileFinder_NativeReflection implements ClassFileFinderInterface {

  /**
   * {@inheritdoc}
   */
  public function find(string $class): ?string {
    try {
      $rc = new \ReflectionClass($class);
    }
    catch (\ReflectionException $e) {
      return null;
    }
    return $rc->getFileName();
  }

}
