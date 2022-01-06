<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolVisitor;

interface SymbolVisitorInterface {

  /**
   * @param class-string $class
   * @param array<string, string> $imports
   * @param list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface> $attributes
   */
  public function addClass(string $class, array $imports, array $attributes): void;

  /**
   * @param class-string $class
   * @param string $property
   * @param list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface> $attributes
   */
  public function addProperty(string $class, string $property, array $attributes): void;

  /**
   * @param class-string $class
   * @param string $constant
   * @param list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface> $attributes
   */
  public function addClassConstant(string $class, string $constant, array $attributes): void;

  /**
   * @param class-string $class
   * @param string $method
   * @param list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface> $attributes
   */
  public function addMethod(string $class, string $method, array $attributes): void;

  /**
   * @param class-string $class
   * @param string $method
   * @param string $param
   * @param list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface> $attributes
   */
  public function addMethodParameter(string $class, string $method, string $param, array $attributes): void;

  public function methodComplete(string $class, string $method): void;

  /**
   * @param class-string $class
   */
  public function classComplete(string $class): void;

  /**
   * @param string $function
   * @param array<string, string> $imports
   * @param list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface> $attributes
   */
  public function addFunction(string $function, array $imports, array $attributes): void;

  /**
   * @param string $function
   * @param string $param
   * @param list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface> $attributes
   */
  public function addFunctionParameter(string $function, string $param, array $attributes): void;

  public function functionComplete(string $function): void;

}
