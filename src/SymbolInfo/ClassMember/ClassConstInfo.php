<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\ClassMember;

use Donquixote\QuickAttributes\SymbolInfo\Shared\SymbolInfoBase;

class ClassConstInfo extends SymbolInfoBase implements ClassConstInfoInterface {

  public function getMemberId(): string {
    return $this->getName();
  }

}
