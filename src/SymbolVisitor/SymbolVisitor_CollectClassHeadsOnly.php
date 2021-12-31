<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolVisitor;

use Donquixote\QuickAttributes\Value\RawSymbolInfo;

class SymbolVisitor_CollectClassHeadsOnly extends SymbolVisitor_NoOp {

  /**
   * @var array<class-string, \Donquixote\QuickAttributes\Value\RawSymbolInfo>
   */
  private $classes = [];

  /**
   * @return array<class-string, \Donquixote\QuickAttributes\Value\RawSymbolInfo>
   */
  public function getClasses(): array {
    return $this->classes;
  }

  /**
   * @inheritDoc
   */
  public function addClass(string $class, array $imports, array $attrComments): void {
    $this->classes[$class] = RawSymbolInfo::forTopLevelSymbol($attrComments, $imports);
  }

}
