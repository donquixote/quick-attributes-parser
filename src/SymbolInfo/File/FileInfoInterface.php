<?php

/**
 * @file
 */

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\File;

use Donquixote\QuickAttributes\SymbolInfo\ClassLike\ClassInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\FunctionLike\FunctionInfoInterface;

interface FileInfoInterface {

  /**
   * @param class-string $name
   *
   * @return \Donquixote\QuickAttributes\SymbolInfo\ClassLike\ClassInfoInterface|null
   */
  public function findClass(string $name): ?ClassInfoInterface;

  /**
   * @param callable-string $name
   *
   * @return \Donquixote\QuickAttributes\SymbolInfo\FunctionLike\FunctionInfoInterface|null
   */
  public function findFunction(string $name): ?FunctionInfoInterface;

  /**
   * @return \Iterator<int, ClassInfoInterface>
   */
  public function readClasses(): \Iterator;

  /**
   * @return \Iterator<int, FunctionInfoInterface>
   */
  public function readFunctions(): \Iterator;

  /**
   * @return \Iterator<int, ClassInfoInterface|FunctionInfoInterface>
   */
  public function readElements(): \Iterator;

}
