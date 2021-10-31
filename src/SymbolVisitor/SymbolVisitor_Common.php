<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolVisitor;

class SymbolVisitor_Common implements SymbolVisitorInterface {

  /**
   * @var array
   */
  private array $currentInfo = [];

  /**
   * @var array<class-string, array>
   */
  private array $classes = [];

  /**
   * @var array<class-string, array>
   */
  private array $properties = [];

  /**
   * @var array<class-string, array>
   */
  private array $classConstants = [];

  private array $methods = [];

  private array $methodParameters = [];

  private array $functions = [];

  private array $functionParameters = [];

  public function clear(): void {
    $this->currentInfo = [];
  }

  public function addClass(string $class): void {
    $this->classes[$class] = $this->currentInfo;
    $this->currentInfo = [];
  }

  public function addProperty(string $class, string $property): void {
    $this->properties[$class][$property] = $this->currentInfo;
    $this->currentInfo = [];
  }

  public function addClassConstant(string $class, string $constant): void {
    $this->classConstants[$class][$constant] = $this->currentInfo;
    $this->currentInfo = [];
  }

  public function addMethod(string $class, string $method): void {
    $this->methods[$class][$method] = $this->currentInfo;
    $this->currentInfo = [];
  }

  public function addMethodParameter(string $class, string $method, string $param): void {
    $this->methodParameters[$class][$method][$param] = $this->currentInfo;
    $this->currentInfo = [];
  }

  public function addFunction(string $function): void {
    $this->functions[$function] = $this->currentInfo;
    $this->currentInfo = [];
  }

  public function addFunctionParameter(string $function, string $param): void {
    // TODO: Implement addFunctionParameter() method.
  }

  public function addDocComment(string $docComment): void {
    // TODO: Implement addDocComment() method.
  }

  public function addAttributeComment(string $attrComment): void {
    // TODO: Implement addAttributeComment() method.
  }

  public function addModifier(string $modifier): void {
    // TODO: Implement addModifier() method.
  }

  public function addParentClass(string $class): void {
    // TODO: Implement addParentClass() method.
  }

  public function addParentInterface(string $interface): void {
    // TODO: Implement addParentInterface() method.
  }

  public function finishClass(): void {
    // TODO: Implement finishClass() method.
  }

  public function finishFile(): void {
    // TODO: Implement finishFile() method.
  }

  public function getClasses(): array {
    return $this->classes;
  }

  public function getFunctions(): array {
    return $this->functions;
  }

}
