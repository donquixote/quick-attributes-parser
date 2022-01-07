<?php

/**
 * @file
 */

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\ClassLike;

use Donquixote\QuickAttributes\SymbolInfo\ClassMember\ClassConstInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\ClassMember\MethodInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\ClassMember\PropertyInfoInterface;

interface ClassBodyInfoInterface {

  /**
   * @param string $name
   *
   * @return PropertyInfoInterface|null
   */
  public function findProperty(string $name): ?PropertyInfoInterface;

  /**
   * @param string $name
   *
   * @return ClassConstInfoInterface|null
   */
  public function findConstant(string $name): ?ClassConstInfoInterface;

  /**
   * @param string $name
   *
   * @return MethodInfoInterface|null
   */
  public function findMethod(string $name): ?MethodInfoInterface;

  /**
   * @return \Iterator<int, PropertyInfoInterface>
   */
  public function readProperties(): \Iterator;

  /**
   * @return \Iterator<int, ClassConstInfoInterface>
   */
  public function readConstants(): \Iterator;

  /**
   * @return \Iterator<int, MethodInfoInterface>
   */
  public function readMethods(): \Iterator;

  /**
   * @return \Iterator<int, PropertyInfoInterface|ClassConstInfoInterface|MethodInfoInterface>
   */
  public function readMembers(): \Iterator;

}
