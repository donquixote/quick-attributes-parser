<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\ClassMember;

use Donquixote\QuickAttributes\SymbolInfo\Shared\LocalSymbolInfoBase;
use Donquixote\QuickAttributes\SymbolVisitor\FunctionLike\ParamVisitorAndInfoTrait;
use Donquixote\QuickAttributes\SymbolVisitor\FunctionLike\ParamVisitorInterface;

class MethodInfo extends LocalSymbolInfoBase implements MethodInfoInterface, ParamVisitorInterface {

  use ParamVisitorAndInfoTrait;

  /**
   * Constructor.
   *
   * @param string $name
   * @param list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface> $attributes
   * @param \Iterator<int, true> $it
   */
  public function __construct(string $name, array $attributes, \Iterator $it) {
    parent::__construct($name, $attributes);
    $this->it = $it;
  }

  public function getMemberId(): string {
    return $this->getName() . '()';
  }

}
