<?php

/**
 * @file
 */

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\Shared;

interface SymbolInfoInterface extends AttributesInfoInterface {

  /**
   * Gets a shortname for member symbols, or a QN for global symbols.
   *
   * @return string
   */
  public function getName(): string;

}
