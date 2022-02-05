<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\Attributes;

use Donquixote\QuickAttributes\Builder\Arguments\ArgumentsBuilder;
use Donquixote\QuickAttributes\Builder\Arguments\ArgumentsBuilderInterface;
use Donquixote\QuickAttributes\RawAttribute\RawAttribute_Arguments;
use Donquixote\QuickAttributes\SymbolInfo\Shared\AttributesInfoInterface;

class AttributesBuilder implements AttributesBuilderInterface, AttributesInfoInterface {

  /**
   * @var list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface>
   */
  private $attributes = [];

  public static function start(): self {
    return new self();
  }

  /**
   * @param class-string $name
   *
   * @return \Donquixote\QuickAttributes\Builder\Arguments\ArgumentsBuilderInterface
   */
  public function addAttribute(string $name): ArgumentsBuilderInterface {
    $argsBuilder = ArgumentsBuilder::start();
    $this->attributes[] = new RawAttribute_Arguments($name, $argsBuilder);
    return $argsBuilder;
  }

  /**
   * @return list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface>
   */
  public function getAttributes(): array {
    return $this->attributes;
  }

}
