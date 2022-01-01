<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolVisitor;

class SymbolVisitor_CollectInfo implements SymbolVisitorInterface {

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
   * @inheritDoc
   */
  public function addClass(string $class, array $imports, array $attributes): void {
    $this->importss[$class] = $imports;
    $this->attributess[$class] = $attributes;
  }

  /**
   * @inheritDoc
   */
  public function addProperty(string $class, string $property, array $attributes): void {
    $this->attributess[$class . '::$' . $property] = $attributes;
  }

  /**
   * @inheritDoc
   */
  public function addClassConstant(string $class, string $constant, array $attributes): void {
    $this->attributess[$class . '::' . $constant] = $attributes;
  }

  /**
   * @inheritDoc
   */
  public function addMethod(string $class, string $method, array $attributes): void {
    $this->attributess[$class . '::' . $method . '()'] = $attributes;
  }

  /**
   * @inheritDoc
   */
  public function addMethodParameter(string $class, string $method, string $param, array $attributes): void {
    $this->attributess[$class . '::' . $method . '($' . $param . ')'] = $attributes;
  }

  /**
   * @inheritDoc
   */
  public function addFunction(string $function, array $imports, array $attributes): void {
    $this->importss[$function . '()'] = $imports;
    $this->attributess[$function . '()'] = $attributes;
  }

  /**
   * @inheritDoc
   */
  public function addFunctionParameter(string $function, string $param, array $attributes): void {
    $this->attributess[$function . '($' . $param . ')'] = $attributes;
  }

}
