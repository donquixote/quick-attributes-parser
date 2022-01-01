<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolVisitor;

class SymbolVisitor_NoOp implements SymbolVisitorInterface {

  public function addClass(string $class, array $imports, array $attributes): void {
    // Do nothing.
  }

  public function addProperty(string $class, string $property, array $attributes): void {
    // Do nothing.
  }

  public function addClassConstant(string $class, string $constant, array $attributes): void {
    // Do nothing.
  }

  public function addMethod(string $class, string $method, array $attributes): void {
    // Do nothing.
  }

  public function addMethodParameter(string $class, string $method, string $param, array $attributes): void {
    // Do nothing.
  }

  public function addFunction(string $function, array $imports, array $attributes): void {
    // Do nothing.
  }

  public function addFunctionParameter(string $function, string $param, array $attributes): void {
    // Do nothing.
  }

}
