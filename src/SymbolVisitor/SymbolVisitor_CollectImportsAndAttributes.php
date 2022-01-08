<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolVisitor;

use Donquixote\QuickAttributes\SymbolVisitor\ClassLike\ClassMemberVisitor_CollectAttributes;
use Donquixote\QuickAttributes\SymbolVisitor\ClassLike\ClassMemberVisitorInterface;
use Donquixote\QuickAttributes\SymbolVisitor\FunctionLike\ParamVisitor_CollectAttributes;
use Donquixote\QuickAttributes\SymbolVisitor\FunctionLike\ParamVisitorInterface;

class SymbolVisitor_CollectImportsAndAttributes implements SymbolVisitorInterface {

  private array $importss;

  /**
   * @var array<string, list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface>>
   */
  private array $attributess;

  /**
   * Constructor.
   *
   * @param array<string, string> $importss
   * @param array<string, list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface<object>>> $attributess
   */
  public function __construct(array &$importss, array &$attributess) {
    $this->importss =& $importss;
    $this->attributess =& $attributess;
  }

  /**
   * @inheritDoc
   */
  public function addClass(string $name, array $imports, array $attributes): ClassMemberVisitorInterface {
    $this->importss[$name] = $imports;
    $this->attributess[$name] = $attributes;
    /** @psalm-suppress MixedArgumentTypeCoercion */
    return new ClassMemberVisitor_CollectAttributes(
      $this->attributess,
      $name);
  }

  /**
   * @inheritDoc
   */
  public function addFunction(string $name, array $imports, array $attributes): ParamVisitorInterface {
    $this->importss[$name . '()'] = $imports;
    $this->attributess[$name . '()'] = $attributes;
    /** @psalm-suppress MixedArgumentTypeCoercion */
    return new ParamVisitor_CollectAttributes(
      $this->attributess,
      $name);
  }

}
