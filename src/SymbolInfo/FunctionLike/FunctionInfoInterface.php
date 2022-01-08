<?php

/**
 * @file
 */

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\FunctionLike;

use Donquixote\QuickAttributes\SymbolInfo\Shared\GlobalSymbolInfoInterface;

interface FunctionInfoInterface extends FunctionLikeInfoInterface, GlobalSymbolInfoInterface {

  /**
   * Gets the function QN including namespace.
   *
   * @return callable-string
   */
  public function getName(): string;

}
