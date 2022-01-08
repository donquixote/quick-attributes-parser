<?php

/**
 * @file
 */

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\Shared;

interface GlobalSymbolInfoInterface extends SymbolInfoInterface {

  /**
   * Gets an id to distinguish from other global symbols.
   *
   * @return string
   */
  public function getId(): string;

  /**
   * Gets the qualified name.
   *
   * @return class-string|callable-string
   */
  public function getName(): string;

  /**
   * @return array<string, string>
   */
  public function getImports(): array;

}
