<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\AttributeReader;

use Donquixote\QuickAttributes\AttributesList\AttributesList_Fallback;
use Donquixote\QuickAttributes\AttributesList\AttributesListInterface;
use Donquixote\QuickAttributes\Registry\SymbolInfoRegistry;
use Donquixote\QuickAttributes\Value\SymbolHandle;

class AttributeReader_Fallback implements AttributeReaderInterface {

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
  public function read(SymbolHandle $symbol): ?AttributesListInterface {
    $rawAttributes = $this->registry->symbolGetAttributes($symbol);
    if (!$rawAttributes) {
      return null;
    }
    return new AttributesList_Fallback($rawAttributes);
  }

}
