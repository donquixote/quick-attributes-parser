<?php

/**
 * @file
 */

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\ClassLike;

use Donquixote\QuickAttributes\SymbolInfo\FunctionLike\FunctionLikeInfoInterface;

interface FunctionInfoInterface extends FunctionLikeInfoInterface, GlobalSymbolInfoInterface {

  /**
   * Gets the function QN including namespace.
   *
   * @return callable-string
   */
  public function getName(): string;

}
