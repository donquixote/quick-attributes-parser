<?php

/**
 * @file
 */

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\FunctionLike;

use Donquixote\QuickAttributes\SymbolInfo\Parameter\ParamInfoInterface;

interface ParametersInfoInterface {

  /**
   * @param string $name
   *
   * @return \Donquixote\QuickAttributes\SymbolInfo\Parameter\ParamInfoInterface|null
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function findParameter(string $name): ?ParamInfoInterface;

  /**
   * @return \Iterator<int, ParamInfoInterface>
   */
  public function readParameters(): \Iterator;

}
