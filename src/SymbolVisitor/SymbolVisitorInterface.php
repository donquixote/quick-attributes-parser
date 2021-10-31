<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolVisitor;

interface SymbolVisitorInterface {
  
  public function clear(): void;

  /**
   * @param class-string $class
   */
  public function addClass(string $class): void;

  /**
   * @param class-string $class
   * @param string $property
   */
  public function addProperty(string $class, string $property): void;

  /**
   * @param class-string $class
   * @param string $constant
   */
  public function addClassConstant(string $class, string $constant): void;

  /**
   * @param class-string $class
   * @param string $method
   */
  public function addMethod(string $class, string $method): void;

  /**
   * @param class-string $class
   * @param string $method
   * @param string $param
   */
  public function addMethodParameter(string $class, string $method, string $param): void;

  /**
   * @param string $function
   */
  public function addFunction(string $function): void;

  /**
   * @param string $function
   * @param string $param
   */
  public function addFunctionParameter(string $function, string $param): void;

  /**
   * @param string $docComment
   */
  public function addDocComment(string $docComment): void;

  /**
   * @param string $attrComment
   */
  public function addAttributeComment(string $attrComment): void;

  /**
   * @param string $modifier
   */
  public function addModifier(string $modifier): void;

  /**
   * @param class-string $class
   */
  public function addParentClass(string $class): void;

  /**
   * @param string $interface
   */
  public function addParentInterface(string $interface): void;

  public function finishClass(): void;

  public function finishFile(): void;

}
