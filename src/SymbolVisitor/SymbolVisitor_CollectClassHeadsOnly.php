<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolVisitor;

use Donquixote\QuickAttributes\SymbolVisitor\ClassLike\ClassMemberVisitorInterface;

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
  public function addClass(string $name, array $imports, array $attributes): ClassMemberVisitorInterface {
    $this->classes[$name] = true;
    return parent::addClass($name, $imports, $attributes);
  }

}
