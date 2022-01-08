<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\ClassMember;

use Donquixote\QuickAttributes\SymbolInfo\Shared\LocalSymbolInfoBase;

class PropertyInfo extends LocalSymbolInfoBase implements PropertyInfoInterface {

  public function getMemberId(): string {
    return '$' . $this->getName();
  }

}
