<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolVisitor\File;

use Donquixote\QuickAttributes\SymbolVisitor\ClassLike\ClassMemberVisitor_NoOp;
use Donquixote\QuickAttributes\SymbolVisitor\ClassLike\ClassMemberVisitorInterface;
use Donquixote\QuickAttributes\SymbolVisitor\FunctionLike\ParamVisitor_NoOp;
use Donquixote\QuickAttributes\SymbolVisitor\FunctionLike\ParamVisitorInterface;

class SymbolVisitor_NoOp implements SymbolVisitorInterface {

  public function addClass(string $name, array $imports, array $attributes): ClassMemberVisitorInterface {
    return new ClassMemberVisitor_NoOp();
  }

  public function addFunction(string $name, array $imports, array $attributes): ParamVisitorInterface {
    return new ParamVisitor_NoOp();
  }

}
