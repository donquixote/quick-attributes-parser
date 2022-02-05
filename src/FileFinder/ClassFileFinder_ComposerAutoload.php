<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\FileFinder;

use Composer\Autoload\ClassLoader;
use Donquixote\QuickAttributes\Util\ComposerUtil;

class ClassFileFinder_ComposerAutoload implements ClassFileFinderInterface {

  /**
   * @var \Composer\Autoload\ClassLoader
   */
  private ClassLoader $classLoader;

  /**
   * Constructor.
   *
   * @param \Composer\Autoload\ClassLoader $classLoader
   *
   * @noinspection InterfacesAsConstructorDependenciesInspection
   */
  public function __construct(ClassLoader $classLoader) {
    $this->classLoader = $classLoader;
  }

  public static function create(): self {
    return new self(ComposerUtil::getActiveClassLoader());
  }

  /**
   * {@inheritdoc}
   */
  public function find(string $class): ?string {
    return $this->classLoader->findFile($class) ?: null;
  }

}
