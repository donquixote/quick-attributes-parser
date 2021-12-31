<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolVisitor;

use Donquixote\QuickAttributes\Value\RawSymbolInfo;

class SymbolVisitor_CollectRawSymbolInfo implements SymbolVisitorInterface {

  /**
   * @var array<string, \Donquixote\QuickAttributes\Value\RawSymbolInfo>
   */
  private $info = [];

  /**
   * @return array<string, \Donquixote\QuickAttributes\Value\RawSymbolInfo>
   */
  public function getAll(): array {
    return $this->info;
  }

  /**
   * @param string $key
   *
   * @return \Donquixote\QuickAttributes\Value\RawSymbolInfo|null
   */
  public function getForKey(string $key): ?RawSymbolInfo {
    return $this->info[$key] ?? null;
  }

  /**
   * @inheritDoc
   */
  public function addClass(string $class, array $imports, array $attrComments): void {
    $this->info[$class] = RawSymbolInfo::forTopLevelSymbol($attrComments, $imports);
  }

  /**
   * @inheritDoc
   */
  public function addProperty(string $class, string $property, array $imports, array $attrComments): void {
    $this->info[$class . '::$' . $property] = RawSymbolInfo::forInnerSymbol($attrComments);
  }

  /**
   * @inheritDoc
   */
  public function addClassConstant(string $class, string $constant, array $imports, array $attrComments): void {
    $this->info[$class . '::' . $constant] = RawSymbolInfo::forInnerSymbol($attrComments);
  }

  /**
   * @inheritDoc
   */
  public function addMethod(string $class, string $method, array $imports, array $attrComments): void {
    $this->info[$class . '::' . $method . '()'] = RawSymbolInfo::forInnerSymbol($attrComments);
  }

  /**
   * @inheritDoc
   */
  public function addMethodParameter(string $class, string $method, string $param, array $imports, array $attrComments): void {
    $this->info[$class . '::' . $method . '($' . $param . ')'] = RawSymbolInfo::forInnerSymbol($attrComments);
  }

  /**
   * @inheritDoc
   */
  public function addFunction(string $function, array $imports, array $attrComments): void {
    $this->info[$function . '()'] = RawSymbolInfo::forTopLevelSymbol($attrComments, $imports);
  }

  /**
   * @inheritDoc
   */
  public function addFunctionParameter(string $function, string $param, array $imports, array $attrComments): void {
    $this->info[$function . '($' . $param . ')'] = RawSymbolInfo::forInnerSymbol($attrComments);
  }

}
