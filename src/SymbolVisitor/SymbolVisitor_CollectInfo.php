<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolVisitor;

class SymbolVisitor_CollectInfo implements SymbolVisitorInterface {

  /**
   * @var array<string, array<string, string>>
   */
  private array $importss = [];

  /**
   * @var array<string, list<string>>
   */
  private array $attrCommentss = [];

  /**
   * @return array<string, array<string, string>>
   */
  public function getImportss(): array {
    return $this->importss;
  }

  /**
   * @return array<string, list<string>>
   */
  public function getAttrCommentss(): array {
    return $this->attrCommentss;
  }

  /**
   * @inheritDoc
   */
  public function addClass(string $class, array $imports, array $attrComments): void {
    $this->importss[$class] = $imports;
    $this->attrCommentss[$class] = $attrComments;
  }

  /**
   * @inheritDoc
   */
  public function addProperty(string $class, string $property, array $imports, array $attrComments): void {
    $this->attrCommentss[$class . '::$' . $property] = $attrComments;
  }

  /**
   * @inheritDoc
   */
  public function addClassConstant(string $class, string $constant, array $imports, array $attrComments): void {
    $this->attrCommentss[$class . '::' . $constant] = $attrComments;
  }

  /**
   * @inheritDoc
   */
  public function addMethod(string $class, string $method, array $imports, array $attrComments): void {
    $this->attrCommentss[$class . '::' . $method . '()'] = $attrComments;
  }

  /**
   * @inheritDoc
   */
  public function addMethodParameter(string $class, string $method, string $param, array $imports, array $attrComments): void {
    $this->attrCommentss[$class . '::' . $method . '($' . $param . ')'] = $attrComments;
  }

  /**
   * @inheritDoc
   */
  public function addFunction(string $function, array $imports, array $attrComments): void {
    $this->importss[$function . '()'] = $imports;
    $this->attrCommentss[$function . '()'] = $attrComments;
  }

  /**
   * @inheritDoc
   */
  public function addFunctionParameter(string $function, string $param, array $imports, array $attrComments): void {
    $this->attrCommentss[$function . '($' . $param . ')'] = $attrComments;
  }

}
