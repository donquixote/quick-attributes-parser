<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolVisitor;

interface SymbolVisitorInterface {

  /**
   * @param class-string $class
   * @param array<string, string> $imports
   * @param list<string> $attrComments
   */
  public function addClass(string $class, array $imports, array $attrComments): void;

  /**
   * @param class-string $class
   * @param string $property
   * @param array<string, string> $imports
   * @param list<string> $attrComments
   */
  public function addProperty(string $class, string $property, array $imports, array $attrComments): void;

  /**
   * @param class-string $class
   * @param string $constant
   * @param array<string, string> $imports
   * @param list<string> $attrComments
   */
  public function addClassConstant(string $class, string $constant, array $imports, array $attrComments): void;

  /**
   * @param class-string $class
   * @param string $method
   * @param array<string, string> $imports
   * @param list<string> $attrComments
   */
  public function addMethod(string $class, string $method, array $imports, array $attrComments): void;

  /**
   * @param class-string $class
   * @param string $method
   * @param string $param
   * @param array<string, string> $imports
   * @param list<string> $attrComments
   */
  public function addMethodParameter(string $class, string $method, string $param, array $imports, array $attrComments): void;

  /**
   * @param string $function
   * @param array<string, string> $imports
   * @param list<string> $attrComments
   */
  public function addFunction(string $function, array $imports, array $attrComments): void;

  /**
   * @param string $function
   * @param string $param
   * @param array<string, string> $imports
   * @param list<string> $attrComments
   */
  public function addFunctionParameter(string $function, string $param, array $imports, array $attrComments): void;

}
