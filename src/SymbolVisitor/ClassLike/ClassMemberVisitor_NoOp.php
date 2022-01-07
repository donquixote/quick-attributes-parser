<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolVisitor\ClassLike;

use Donquixote\QuickAttributes\SymbolVisitor\FunctionLike\ParamVisitor_NoOp;
use Donquixote\QuickAttributes\SymbolVisitor\FunctionLike\ParamVisitorInterface;

class ClassMemberVisitor_NoOp implements ClassMemberVisitorInterface {

  public function addProperty(string $name, array $attributes): void {
    // Do nothing.
  }

  public function addConstant(string $name, array $attributes): void {
    // Do nothing.
  }

  public function addMethod(string $name, array $attributes): ParamVisitorInterface {
    return new ParamVisitor_NoOp();
  }

  public function markAsComplete(): void {
    // Do nothing.
  }

}
