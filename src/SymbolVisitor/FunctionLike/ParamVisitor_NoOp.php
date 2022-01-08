<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolVisitor\FunctionLike;

class ParamVisitor_NoOp implements ParamVisitorInterface {

  public function addParameter(string $name, array $attributes): void {
    // Do nothing.
  }

  public function markAsComplete(): void {
    // Do nothing.
  }

}
