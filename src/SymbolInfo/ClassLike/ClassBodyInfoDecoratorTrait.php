<?php

/**
 * @file
 */

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\ClassLike;

use Donquixote\QuickAttributes\SymbolInfo\ClassMember\ClassConstInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\ClassMember\MethodInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\ClassMember\PropertyInfoInterface;

trait ClassBodyInfoDecoratorTrait {

  private ClassBodyInfoInterface $body;

  public function findProperty(string $name): ?PropertyInfoInterface {
    return $this->body->findProperty($name);
  }

  public function findConstant(string $name): ?ClassConstInfoInterface {
    return $this->body->findConstant($name);
  }

  public function findMethod(string $name): ?MethodInfoInterface {
    return $this->body->findMethod($name);
  }

  /**
   * @return \Iterator<int, PropertyInfoInterface>
   */
  public function readProperties(): \Iterator {
    return $this->body->readProperties();
  }

  /**
   * @return \Iterator<int, ClassConstInfoInterface>
   */
  public function readConstants(): \Iterator {
    return $this->body->readConstants();
  }

  /**
   * @return \Iterator<int, MethodInfoInterface>
   */
  public function readMethods(): \Iterator {
    return $this->body->readMethods();
  }

  /**
   * @return \Iterator<int, PropertyInfoInterface|ClassConstInfoInterface|MethodInfoInterface>
   */
  public function readMembers(): \Iterator {
    return $this->body->readMembers();
  }

}
