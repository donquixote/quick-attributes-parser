<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo;

use Donquixote\QuickAttributes\Lookup\LookupInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ClassInfo extends SymbolInfoBase implements GlobalSymbolInfoInterface {

  /**
   * @var \Donquixote\QuickAttributes\Lookup\LookupInterface
   */
  private LookupInterface $lookup;

  /**
   * @var array<string, string>
   */
  private array $imports;

  private string $prefix = '?::';

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\Lookup\LookupInterface $lookup
   * @param string $name
   * @param string $id
   *
   * @return static|null
   */
  public static function create(LookupInterface $lookup, string $name, string $id): ?self {
    $instance = parent::create($lookup, $name, $id);
    if ($instance === null) {
      return null;
    }
    $imports = $lookup->keyGetImports($name);
    if ($imports === null) {
      return null;
    }
    $instance->lookup = $lookup;
    $instance->imports = $imports;
    $instance->prefix = $name . '::';
    return $instance;
  }

  /**
   * @return array<string, string>
   */
  public function getImports(): array {
    return $this->imports;
  }

  public function findConstant(string $const): ?ClassConstInfo {
    return ClassConstInfo::create(
      $this->lookup,
      $const,
      $this->prefix . $const);
  }

  public function findProperty(string $property): ?PropertyInfo {
    return PropertyInfo::create(
      $this->lookup,
      $property,
      $this->prefix . '$' . $property);
  }

  public function findMethod(string $method): ?MethodInfo {
    return MethodInfo::create(
      $this->lookup,
      $method,
      $this->prefix . $method . '()');
  }

  /**
   * @return \Iterator<int, PropertyInfo>
   */
  public function readProperties(): \Iterator {
    foreach ($this->lookup->keyReadChildNames($this->getName()) as $key) {
      if ($key[0] === '$') {
        yield PropertyInfo::createExpected(
          $this->lookup,
          \substr($key, 1),
          $this->prefix . $key);
      }
    }
  }

  /**
   * @return \Iterator<int, ClassConstInfo>
   */
  public function readConstants(): \Iterator {
    foreach ($this->lookup->keyReadChildNames($this->getName()) as $key) {
      if ($key[0] !== '$' && \substr($key, -2) !== '()') {
        yield ClassConstInfo::createExpected(
          $this->lookup,
          $key,
          $this->prefix . $key);
      }
    }
  }

  /**
   * @return \Iterator<int, MethodInfo>
   */
  public function readMethods(): \Iterator {
    foreach ($this->lookup->keyReadChildNames($this->getName()) as $key) {
      if (\substr($key, -2) === '()') {
        yield MethodInfo::createExpected(
          $this->lookup,
          \substr($key, 0, -2),
          $this->prefix . $key);
      }
    }
  }

  /**
   * @return \Iterator<int, \Donquixote\QuickAttributes\SymbolInfo\SymbolInfoInterface>
   * @psalm-return \Iterator<int, PropertyInfo|MethodInfo|ClassConstInfo>
   */
  public function readMembers(): \Iterator {
    $offset = 0;
    $prefix = $this->getName() . '::';
    foreach ($this->lookup->keyReadChildNames($this->getName(), $offset) as $key) {
      \assert(\preg_match('@^\$?\w+(?:\(\))?$@', $key), $key);
      if ($key[0] === '$') {
        // Property.
        yield PropertyInfo::createExpected(
          $this->lookup,
          \substr($key, 1),
          $prefix . $key);
      }
      elseif (\substr($key, -2) === '()') {
        // Method.
        yield MethodInfo::createExpected(
          $this->lookup,
          \substr($key, 0, -2),
          $prefix . $key);
      }
      else {
        // Class constant.
        yield ClassConstInfo::createExpected(
          $this->lookup,
          $key,
          $prefix . $key);
      }
    }
  }

}