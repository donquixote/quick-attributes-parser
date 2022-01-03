<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo;

use Donquixote\QuickAttributes\Lookup\LookupInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ClassInfo implements GlobalSymbolInfoInterface {

  /**
   * @var \Donquixote\QuickAttributes\Lookup\LookupInterface
   */
  private LookupInterface $lookup;

  /**
   * @var class-string
   */
  private string $class;

  /**
   * @var array<string, string>
   */
  private array $imports;

  /**
   * @var list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface>
   */
  private array $attributes;

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\Lookup\LookupInterface $lookup
   * @param string $class
   *
   * @return static
   *
   * @throws \RuntimeException
   */
  public static function createExpected(LookupInterface $lookup, string $class): ?self {
    $instance = static::create($lookup, $class);
    if ($instance === null) {
      throw new \RuntimeException('Class not found.');
    }
    return $instance;
  }

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\Lookup\LookupInterface $lookup
   * @param string $class
   *
   * @return static|null
   */
  public static function create(LookupInterface $lookup, string $class): ?self {
    $attributes = $lookup->keyGetAttributes($class);
    if ($attributes === null) {
      return null;
    }
    $imports = $lookup->keyGetImports($class);
    if ($imports === null) {
      return null;
    }
    $instance = new static();
    $instance->lookup = $lookup;
    $instance->class = $class;
    $instance->attributes = $attributes;
    $instance->imports = $imports;
    return $instance;
  }

  final private function __construct() {}

  /**
   * @return array<string, string>
   */
  public function getImports(): array {
    return $this->imports;
  }

  public function getAttributes(): array {
    return $this->attributes;
  }

  public function constGetAttributes(string $const): ?array {
    return $this->lookup->keyGetAttributes($this->class . '::' . $const);
  }

  public function propertyGetAttributes(string $property): ?array {
    return $this->lookup->keyGetAttributes($this->class . '::$' . $property);
  }

  public function readProperties(): \Iterator {
    $offset = 0;
    foreach ($this->lookup->keyReadChildNames($this->class, $offset) as $key) {
      if (\substr($key, -2) === '()') {
        // Method.
        $method = \substr($key, 0, -2);
        yield $method => $this->methodGetInfo($method);
      }
    }
  }

  public function methodGetAttributes(string $method): ?array {
    return $this->lookup->keyGetAttributes($this->class . '::' . $method . '()');
  }

  public function methodGetInfo(string $method): ?MethodInfo {
    return MethodInfo::create($this->lookup, $this->class . '::' . $method);
  }

  /**
   * @return \Iterator<string, \Donquixote\QuickAttributes\SymbolInfo\MethodInfo>
   */
  public function readMethods(): \Iterator {
    $offset = 0;
    foreach ($this->lookup->keyReadChildNames($this->class, $offset) as $key) {
      if (\substr($key, -2) === '()') {
        // Method.
        $method = \substr($key, 0, -2);
        $methodInfo = $this->methodGetInfo($method);
        if ($methodInfo === null) {
          throw new \RuntimeException('Method not found.');
        }
        yield $method => $methodInfo;
      }
    }
  }

  /**
   * @return \Iterator<string, '$'|'function'|'const'>
   */
  public function readMemberTypes(): \Iterator {
    $offset = 0;
    foreach ($this->lookup->keyReadChildNames($this->class, $offset) as $key) {
      \assert(\preg_match('@^\$?\w+(?:\(\))?$@', $key), $key);
      if ($key[0] === '$') {
        // Property.
        yield \substr($key, 1) => '$';
      }
      elseif (\substr($key, -2) === '()') {
        // Method.
        yield \substr($key, 0, -2) => 'function';
      }
      else {
        // Class constant.
        yield $key => 'const';
      }
    }
  }

}
