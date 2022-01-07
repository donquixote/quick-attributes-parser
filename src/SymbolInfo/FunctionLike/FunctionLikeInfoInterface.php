<?php

/**
 * @file
 */

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\FunctionLike;

use Donquixote\QuickAttributes\SymbolInfo\Parameter\ParamInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\Shared\SymbolInfoInterface;

interface FunctionLikeInfoInterface extends SymbolInfoInterface {

  public function getParameter(string $param): ?ParamInfoInterface;

  /**
   * @return \Iterator<int, ParamInfoInterface>
   */
  public function readParameters(): \Iterator;

}
