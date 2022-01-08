<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\Parameter;

use Donquixote\QuickAttributes\SymbolInfo\Shared\AttributesInfoInterface;

interface ParamInfoInterface extends AttributesInfoInterface {

  /**
   * The parameter name.
   *
   * @return string
   */
  public function getName(): string;

}
