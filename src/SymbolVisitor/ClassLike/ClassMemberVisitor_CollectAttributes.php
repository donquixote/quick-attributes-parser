<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolVisitor\ClassLike;

use Donquixote\QuickAttributes\SymbolVisitor\FunctionLike\ParamVisitor_CollectAttributes;
use Donquixote\QuickAttributes\SymbolVisitor\FunctionLike\ParamVisitorInterface;

class ClassMemberVisitor_CollectAttributes implements ClassMemberVisitorInterface {

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
  public function addProperty(string $name, array $attributes): void {
    $this->attributess[$this->prefix . '$' . $name] = $attributes;
  }

  /**
   * @inheritDoc
   */
  public function addConstant(string $name, array $attributes): void {
    $this->attributess[$this->prefix . $name] = $attributes;
  }

  /**
   * @inheritDoc
   */
  public function addMethod(string $name, array $attributes): ParamVisitorInterface {
    $this->attributess[$this->prefix . $name . '()'] = $attributes;
    /** @psalm-suppress MixedArgumentTypeCoercion */
    return new ParamVisitor_CollectAttributes(
      $this->attributess,
      $this->prefix . $name);
  }

  public function markAsComplete(): void {
    // Do nothing.
  }

}
