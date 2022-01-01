<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolVisitor;

class SymbolVisitor_CollectClassHeadsOnly extends SymbolVisitor_NoOp {

  /**
   * @var array<class-string, true>
   */
  private $classes = [];

  /**
   * @return array<class-string, true>
   */
  public function getClasses(): array {
    return $this->classes;
  }

  /**
   * @inheritDoc
   */
  public function addClass(string $class, array $imports, array $attributes): void {
    $this->classes[$class] = true;
  }

}
