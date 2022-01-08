<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolVisitor\ClassLike;

use Donquixote\QuickAttributes\SymbolVisitor\FunctionLike\ParamVisitorInterface;

interface ClassMemberVisitorInterface {

  /**
   * @param string $name
   * @param list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface> $attributes
   */
  public function addProperty(string $name, array $attributes): void;

  /**
   * @param string $name
   * @param list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface> $attributes
   */
  public function addConstant(string $name, array $attributes): void;

  /**
   * @param string $name
   * @param list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface> $attributes
   *
   * @return \Donquixote\QuickAttributes\SymbolVisitor\FunctionLike\ParamVisitorInterface
   */
  public function addMethod(string $name, array $attributes): ParamVisitorInterface;

  public function markAsComplete(): void;

}
