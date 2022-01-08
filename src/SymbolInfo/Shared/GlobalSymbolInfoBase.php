<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\Shared;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
abstract class GlobalSymbolInfoBase extends SymbolInfoBase implements GlobalSymbolInfoInterface {

  /**
   * @var array<string, string>
   */
  private array $imports;

  /**
   * Constructor.
   *
   * @param array<string, string> $imports
   * @param list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface> $attributes
   */
  public function __construct(array $imports, array $attributes) {
    parent::__construct($attributes);
    $this->imports = $imports;
  }

  /**
   * @return array<string, string>
   */
  public function getImports(): array {
    return $this->imports;
  }

}
