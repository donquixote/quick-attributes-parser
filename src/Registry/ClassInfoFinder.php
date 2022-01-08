<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Registry;

use Donquixote\QuickAttributes\ClassFileFinder\ClassFileFinder_ComposerAutoload;
use Donquixote\QuickAttributes\ClassFileFinder\ClassFileFinderInterface;
use Donquixote\QuickAttributes\SymbolInfo\ClassInfo;

class ClassInfoFinder {

  /**
   * @var \Donquixote\QuickAttributes\Registry\FileInfoLoader
   */
  private FileInfoLoader $fileInfoLoader;

  /**
   * @var \Donquixote\QuickAttributes\ClassFileFinder\ClassFileFinderInterface
   */
  private ClassFileFinderInterface $classFileFinder;

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\Registry\FileInfoLoader $fileInfoLoader
   * @param \Donquixote\QuickAttributes\ClassFileFinder\ClassFileFinderInterface $classFileFinder
   */
  public function __construct(FileInfoLoader $fileInfoLoader, ClassFileFinderInterface $classFileFinder) {
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
   * @return \Donquixote\QuickAttributes\SymbolInfo\ClassInfo
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function requireClass(string $class): ClassInfo {
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
   * @return \Donquixote\QuickAttributes\SymbolInfo\ClassInfo|null
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function findClass(string $class): ?ClassInfo {
    $file = $this->classFileFinder->find($class);
    if ($file === null) {
      return null;
    }
    return $this->fileInfoLoader
      ->loadFile($file)
      ->findClass($class);
  }

}