<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\RawAttributesReader;

use Donquixote\QuickAttributes\Registry\SymbolInfoRegistry;
use Donquixote\QuickAttributes\Value\SymbolHandle;

class RawAttributesReader_Fallback implements RawAttributesReaderInterface {

  /**
   * @var \Donquixote\QuickAttributes\Registry\SymbolInfoRegistry
   */
  private SymbolInfoRegistry $registry;

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\Registry\SymbolInfoRegistry $registry
   */
  public function __construct(SymbolInfoRegistry $registry) {
    $this->registry = $registry;
  }

  /**
   * @return self
   */
  public static function create(): self {
    return new self(SymbolInfoRegistry::create());
  }

  /**
   * {@inheritdoc}
   */
  public function read(SymbolHandle $symbol): array {
    return $this->registry->symbolGetAttributes($symbol);
  }

}
