<?php

/**
 * @file
 */

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo;

interface GlobalSymbolInfoInterface extends SymbolInfoInterface {

  /**
   * @return array<string, string>
   */
  public function getImports(): ?array;

}
