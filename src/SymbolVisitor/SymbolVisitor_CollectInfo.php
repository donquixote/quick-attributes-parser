<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolVisitor;

use Donquixote\QuickAttributes\Lookup\LookupInterface;

class SymbolVisitor_CollectInfo implements SymbolVisitorInterface, LookupInterface {

  /**
   * @var list<string>
   */
  private array $toplevelNames = [];

  /**
   * @var array<string, true>
   */
  private array $completed = [];

  /**
   * @var array<string, list<string>>
   */
  private array $childNames = [];

  /**
   * @var array<string, array<string, string>>
   */
  private array $importss = [];

  /**
   * @var array<string, list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface>>
   */
  private array $attributess = [];

  /**
   * @return array<string, array<string, string>>
   */
  public function getImportss(): array {
    return $this->importss;
  }

  public function clearImportss(): void {
    $this->importss = [];
  }

  /**
   * @param string $key
   *
   * @return array<string, string>|null
   */
  public function keyGetImports(string $key): ?array {
    return $this->importss[$key] ?? null;
  }

  /**
   * @return array<string, list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface>>
   */
  public function getAttributess(): array {
    return $this->attributess;
  }

  public function keyIsKnown(string $key): bool {
    return isset($this->attributess[$key]);
  }

  /**
   * @param string $key
   *
   * @return list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface>|null
   */
  public function keyGetAttributes(string $key): ?array {
    return $this->attributess[$key] ?? null;
  }

  /**
   * {@inheritdoc}
   */
  public function readToplevelNames(int &$offset = 0): \Iterator {
    $n = \count($this->toplevelNames);
    for (; $offset < $n; ++$offset) {
      yield $this->toplevelNames[$offset];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function keyReadChildNames(string $key, int &$offset = 0): \Iterator {
    $names = $this->childNames[$key] ?? [];
    $n = \count($names);
    for (; $offset < $n; ++$offset) {
      yield $names[$offset];
    }
    if ($this->completed[$key] ?? false) {
      // All children are known.
      $offset = -1;
    }
  }

  /**
   * @inheritDoc
   */
  public function addClass(string $class, array $imports, array $attributes): void {
    $this->importss[$class] = $imports;
    $this->attributess[$class] = $attributes;
    $this->toplevelNames[] = $class;
  }

  /**
   * @inheritDoc
   */
  public function addProperty(string $class, string $property, array $attributes): void {
    $this->attributess[$class . '::$' . $property] = $attributes;
    $this->childNames[$class][] = '$' . $property;
  }

  /**
   * @inheritDoc
   */
  public function addClassConstant(string $class, string $constant, array $attributes): void {
    $this->attributess[$class . '::' . $constant] = $attributes;
    $this->childNames[$class][] = $constant;
  }

  /**
   * @inheritDoc
   */
  public function addMethod(string $class, string $method, array $attributes): void {
    $this->attributess[$class . '::' . $method . '()'] = $attributes;
    $this->childNames[$class][] = $method . '()';
  }

  /**
   * @inheritDoc
   */
  public function addMethodParameter(string $class, string $method, string $param, array $attributes): void {
    $this->attributess[$class . '::' . $method . '($' . $param . ')'] = $attributes;
    $this->childNames[$class . '::' . $method . '()'][] = $param;
  }

  public function methodComplete(string $class, string $method): void {
    $this->completed[$class . '::' . $method . '()'] = true;
  }

  public function classComplete(string $class): void {
    $this->completed[$class] = true;
  }

  /**
   * @inheritDoc
   */
  public function addFunction(string $function, array $imports, array $attributes): void {
    $name = $function . '()';
    $this->importss[$name] = $imports;
    $this->attributess[$name] = $attributes;
    $this->toplevelNames[] = $name;
  }

  /**
   * @inheritDoc
   */
  public function addFunctionParameter(string $function, string $param, array $attributes): void {
    $this->attributess[$function . '($' . $param . ')'] = $attributes;
    $this->childNames[$function . '()'][] = $param;
  }

  public function functionComplete(string $function): void {
    $this->completed[$function . '()'] = true;
  }

}
