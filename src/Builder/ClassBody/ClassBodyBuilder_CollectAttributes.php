<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\ClassBody;

use Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilder_CollectAttributes;
use Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilderInterface;
use Donquixote\QuickAttributes\Builder\FunctionLike\FunctionLikeBuilder;
use Donquixote\QuickAttributes\Builder\FunctionLike\FunctionLikeBuilderInterface;
use Donquixote\QuickAttributes\Builder\Parameters\ParametersBuilder_CollectAttributes;

class ClassBodyBuilder_CollectAttributes implements ClassBodyBuilderInterface {

  /**
   * @var array<string, list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface>>
   */
  private array $attributess;

  private string $prefix;

  /**
   * Constructor.
   *
   * @param array<string, list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface>> $attributess
   * @param string $class
   */
  public function __construct(array &$attributess, string $class) {
    $this->attributess =& $attributess;
    $this->prefix = $class . '::';
  }

  /**
   * @inheritDoc
   */
  public function addProperty(string $name): AttributesBuilderInterface {
    /** @psalm-suppress MixedArgumentTypeCoercion */
    return new AttributesBuilder_CollectAttributes(
      $this->attributess[$this->prefix . '$' . $name]);
  }

  /**
   * @inheritDoc
   */
  public function addConstant(string $name): AttributesBuilderInterface {
    /** @psalm-suppress MixedArgumentTypeCoercion */
    return new AttributesBuilder_CollectAttributes(
      $this->attributess[$this->prefix . $name]);
  }

  /**
   * @inheritDoc
   */
  public function addMethod(string $name): FunctionLikeBuilderInterface {
    /** @psalm-suppress MixedArgumentTypeCoercion */
    return new FunctionLikeBuilder(
      new AttributesBuilder_CollectAttributes(
        $this->attributess[$this->prefix . $name . '()']),
      new ParametersBuilder_CollectAttributes(
        $this->attributess,
        $this->prefix . $name));
  }

  public function markAsComplete(): void {
    // Do nothing.
  }

}
