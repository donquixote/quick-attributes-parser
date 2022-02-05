<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\ClassMember;

use Donquixote\QuickAttributes\SymbolInfo\FunctionLike\ParametersInfoDecoratorTrait;
use Donquixote\QuickAttributes\SymbolInfo\FunctionLike\ParametersInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\Shared\AttributesInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\Shared\LocalSymbolInfoBase;

class MethodInfo extends LocalSymbolInfoBase implements MethodInfoInterface {

  use ParametersInfoDecoratorTrait;

  /**
   * Constructor.
   *
   * @param string $name
   * @param \Donquixote\QuickAttributes\SymbolInfo\Shared\AttributesInfoInterface $attributes
   * @param \Donquixote\QuickAttributes\SymbolInfo\FunctionLike\ParametersInfoInterface $parameters
   */
  public function __construct(string $name, AttributesInfoInterface $attributes, ParametersInfoInterface $parameters) {
    parent::__construct($name, $attributes);
    $this->parameters = $parameters;
  }

  public function getMemberId(): string {
    return $this->getName() . '()';
  }

}
