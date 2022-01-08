<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolVisitor\ClassLike;

use Donquixote\QuickAttributes\SymbolInfo\ClassMember\ClassConstInfo;
use Donquixote\QuickAttributes\SymbolInfo\ClassMember\ClassConstInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\ClassMember\ClassMemberInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\ClassMember\MethodInfo;
use Donquixote\QuickAttributes\SymbolInfo\ClassMember\MethodInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\ClassMember\PropertyInfo;
use Donquixote\QuickAttributes\SymbolInfo\ClassMember\PropertyInfoInterface;
use Donquixote\QuickAttributes\SymbolVisitor\FunctionLike\ParamVisitorInterface;

/**
 * @see \Donquixote\QuickAttributes\SymbolVisitor\ClassLike\ClassMemberVisitorInterface
 * @see \Donquixote\QuickAttributes\SymbolInfo\ClassLike\ClassBodyInfoInterface
 */
trait ClassMemberVisitorAndInfoTrait {

  /**
   * @var list<PropertyInfoInterface|ClassConstInfoInterface|MethodInfoInterface>
   */
  private array $members = [];

  /**
   * @var array<string, int>
   */
  private array $propertyMap = [];

  /**
   * @var array<string, int>
   */
  private array $constMap = [];

  /**
   * @var array<string, int>
   */
  private array $methodMap = [];

  private bool $complete = false;

  /**
   * @var \Iterator<int, true>
   */
  private \Iterator $it;

  /**
   * @param string $name
   * @param list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface> $attributes
   */
  public function addProperty(string $name, array $attributes): void {
    $this->propertyMap[$name] = \count($this->members);
    $this->members[] = new PropertyInfo($name, $attributes);
  }

  /**
   * @param string $name
   * @param list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface> $attributes
   */
  public function addConstant(string $name, array $attributes): void {
    $this->constMap[$name] = \count($this->members);
    $this->members[] = new ClassConstInfo($name, $attributes);
  }

  /**
   * @param string $name
   * @param list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface> $attributes
   *
   * @return \Donquixote\QuickAttributes\SymbolVisitor\FunctionLike\ParamVisitorInterface
   */
  public function addMethod(string $name, array $attributes): ParamVisitorInterface {
    $this->methodMap[$name] = \count($this->members);
    $this->members[] = $method = new MethodInfo($name, $attributes, $this->it);
    return $method;
  }

  public function markAsComplete(): void {
    $this->complete = true;
  }

  /**
   * @param string $name
   *
   * @return PropertyInfoInterface|null
   */
  public function findProperty(string $name): ?PropertyInfoInterface {
    $member = $this->findMember($name, $this->propertyMap);
    \assert($member instanceof PropertyInfo);
    return $member;
  }

  /**
   * @param string $name
   *
   * @return ClassConstInfoInterface|null
   */
  public function findConstant(string $name): ?ClassConstInfoInterface {
    $member = $this->findMember($name, $this->constMap);
    \assert($member instanceof ClassConstInfo);
    return $member;
  }

  /**
   * @param string $name
   *
   * @return MethodInfoInterface|null
   */
  public function findMethod(string $name): ?MethodInfoInterface {
    $member = $this->findMember($name, $this->methodMap);
    \assert($member instanceof MethodInfoInterface);
    return $member;
  }

  /**
   * @param string $name
   * @param array<string, int> $map
   *
   * @return PropertyInfoInterface|ClassConstInfoInterface|MethodInfoInterface|null
   */
  private function findMember(string $name, array &$map): ?ClassMemberInfoInterface {
    if (null !== ($index = $map[$name] ?? null)) {
      return $this->members[$index];
    }
    while (!$this->complete) {
      if (!$this->it->valid()) {
        throw new \RuntimeException('Parser did not close this class.');
      }
      if (null !== ($index = $map[$name] ?? null)) {
        return $this->members[$index];
      }
      $this->it->next();
    }
    return null;
  }

  /**
   * @return \Iterator<int, PropertyInfoInterface>
   */
  public function readProperties(): \Iterator {
    foreach ($this->readMembers() as $member) {
      if ($member instanceof PropertyInfo) {
        yield $member;
      }
    }
  }

  /**
   * @return \Iterator<int, ClassConstInfoInterface>
   */
  public function readConstants(): \Iterator {
    foreach ($this->readMembers() as $member) {
      if ($member instanceof ClassConstInfo) {
        yield $member;
      }
    }
  }

  /**
   * @return \Iterator<int, MethodInfoInterface>
   */
  public function readMethods(): \Iterator {
    foreach ($this->readMembers() as $member) {
      if ($member instanceof MethodInfoInterface) {
        yield $member;
      }
    }
  }

  /**
   * @return \Iterator<int, PropertyInfoInterface|ClassConstInfoInterface|MethodInfoInterface>
   */
  public function readMembers(): \Iterator {
    if ($this->complete) {
      yield from $this->members;
      return;
    }
    $index = 0;
    while (true) {
      if (isset($this->members[$index])) {
        yield $this->members[$index];
        ++$index;
        continue;
      }
      /** @psalm-suppress TypeDoesNotContainType */
      if ($this->complete) {
        return;
      }
      if (!$this->it->valid()) {
        throw new \RuntimeException('Parser did not close this class.');
      }
      $this->it->next();
    }
  }

}
