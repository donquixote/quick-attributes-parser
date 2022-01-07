<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\ClassMember;

use Donquixote\QuickAttributes\SymbolInfo\Shared\LocalSymbolInfoBase;

class ClassConstInfo extends LocalSymbolInfoBase implements ClassConstInfoInterface {

  public function getMemberId(): string {
    return $this->getName();
  }

}
