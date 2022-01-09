<?php

/**
 * @file
 */

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Registry;

use Donquixote\QuickAttributes\SymbolInfo\ClassLike\ClassInfoInterface;

interface ClassInfoFinderInterface {

  /**
   * @param class-string $class
   *
   * @return \Donquixote\QuickAttributes\SymbolInfo\ClassLike\ClassInfoInterface
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function requireClass(string $class): ClassInfoInterface;

  /**
   * @param class-string $class
   *
   * @return \Donquixote\QuickAttributes\SymbolInfo\ClassLike\ClassInfoInterface|null
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function findClass(string $class): ?ClassInfoInterface;

}
