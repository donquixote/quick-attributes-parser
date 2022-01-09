<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Registry;

use Donquixote\QuickAttributes\FileFinder\ClassFileFinder_ComposerAutoload;
use Donquixote\QuickAttributes\FileFinder\ClassFileFinderInterface;
use Donquixote\QuickAttributes\SymbolInfo\ClassLike\ClassInfoInterface;

class ClassInfoFinder {

  /**
   * @var \Donquixote\QuickAttributes\Registry\FileInfoLoaderInterface
   */
  private FileInfoLoaderInterface $fileInfoLoader;

  /**
   * @var \Donquixote\QuickAttributes\FileFinder\ClassFileFinderInterface
   */
  private ClassFileFinderInterface $classFileFinder;

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\Registry\FileInfoLoaderInterface $fileInfoLoader
   * @param \Donquixote\QuickAttributes\FileFinder\ClassFileFinderInterface $classFileFinder
   */
  public function __construct(FileInfoLoaderInterface $fileInfoLoader, ClassFileFinderInterface $classFileFinder) {
    $this->fileInfoLoader = $fileInfoLoader;
    $this->classFileFinder = $classFileFinder;
  }

  public static function create(): self {
    return new self(
      FileInfoLoader::create(),
      ClassFileFinder_ComposerAutoload::create());
  }

  /**
   * @param class-string $class
   *
   * @return \Donquixote\QuickAttributes\SymbolInfo\ClassLike\ClassInfoInterface
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function requireClass(string $class): ClassInfoInterface {
    $file = $this->classFileFinder->find($class);
    if ($file === null) {
      throw new \RuntimeException("No file found for class $class.");
    }
    $info = $this->fileInfoLoader
      ->loadFile($file)
      ->findClass($class);
    if ($info === null) {
      throw new \RuntimeException("Class $class not found in file $file.");
    }
    return $info;
  }

  /**
   * @param class-string $class
   *
   * @return \Donquixote\QuickAttributes\SymbolInfo\ClassLike\ClassInfoInterface|null
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function findClass(string $class): ?ClassInfoInterface {
    $file = $this->classFileFinder->find($class);
    if ($file === null) {
      return null;
    }
    return $this->fileInfoLoader
      ->loadFile($file)
      ->findClass($class);
  }

}
