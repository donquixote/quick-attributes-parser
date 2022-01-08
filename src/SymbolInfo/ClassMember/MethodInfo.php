<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\ClassMember;

use Donquixote\QuickAttributes\SymbolInfo\FunctionLike\FunctionInfoBase;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class MethodInfo extends FunctionInfoBase implements MethodInfoInterface {

  public function getMemberId(): string {
    return $this->getName() . '()';
  }

}
