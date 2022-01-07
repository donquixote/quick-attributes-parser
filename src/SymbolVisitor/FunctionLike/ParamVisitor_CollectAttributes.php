<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolVisitor\FunctionLike;

class ParamVisitor_CollectAttributes implements ParamVisitorInterface {

  /**
   * @var array<string, list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface>>
   */
  private array $attributess;

  private string $prefix;

  /**
   * Constructor.
   *
   * @param array<string, list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface>> $attributess
   * @param string $function
   */
  public function __construct(array &$attributess, string $function) {
    $this->attributess =& $attributess;
    $this->prefix = $function . '($';
  }

  /**
   * @inheritDoc
   */
  public function addParameter(string $name, array $attributes): void {
    $this->attributess[$this->prefix . $name . ')'] = $attributes;
  }

  public function markAsComplete(): void {
    // Do nothing.
  }

}
