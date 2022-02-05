<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\Parameters;

use Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilder_CollectAttributes;
use Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilderInterface;

class ParametersBuilder_CollectAttributes implements ParametersBuilderInterface {

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
  public function addParameter(string $name): AttributesBuilderInterface {
    /** @psalm-suppress MixedArgumentTypeCoercion */
    return new AttributesBuilder_CollectAttributes(
      $this->attributess[$this->prefix . $name . ')']);
  }

  public function markAsComplete(): void {
    // Do nothing.
  }

}
