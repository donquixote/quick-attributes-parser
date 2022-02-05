<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\Attributes;

use Donquixote\QuickAttributes\Builder\Arguments\ArgumentsBuilder;
use Donquixote\QuickAttributes\Builder\Arguments\ArgumentsBuilderInterface;
use Donquixote\QuickAttributes\RawAttribute\RawAttribute_Arguments;

class AttributesBuilder_CollectAttributes implements AttributesBuilderInterface {

  /**
   * @var list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface>
   */
  private array $attributes;

  /**
   * Constructor.
   *
   * @param list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface>|null $attributes
   */
  public function __construct(?array &$attributes) {
    if ($attributes === null) {
      $attributes = [];
    }
    $this->attributes =& $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function addAttribute(string $name): ArgumentsBuilderInterface {
    $argsBuilder = ArgumentsBuilder::start();
    $this->attributes[] = new RawAttribute_Arguments($name, $argsBuilder);
    return $argsBuilder;
  }

}
