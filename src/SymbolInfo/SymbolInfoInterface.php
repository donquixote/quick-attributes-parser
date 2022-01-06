<?php

/**
 * @file
 */

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo;

interface SymbolInfoInterface {

  /**
   * @return list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface>
   */
  public function getAttributes(): array;

  /**
   * @return string
   */
  public function getName(): string;

  public function getId(): string;

}
