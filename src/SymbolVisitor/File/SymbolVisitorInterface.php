<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolVisitor\File;

use Donquixote\QuickAttributes\SymbolVisitor\ClassLike\ClassMemberVisitorInterface;
use Donquixote\QuickAttributes\SymbolVisitor\FunctionLike\ParamVisitorInterface;

interface SymbolVisitorInterface {

  /**
   * @param class-string $name
   * @param array<string, string> $imports
   * @param list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface> $attributes
   *
   * @return \Donquixote\QuickAttributes\SymbolVisitor\ClassLike\ClassMemberVisitorInterface
   */
  public function addClass(string $name, array $imports, array $attributes): ClassMemberVisitorInterface;

  /**
   * @param callable-string $name
   * @param array<string, string> $imports
   * @param list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface> $attributes
   *
   * @return \Donquixote\QuickAttributes\SymbolVisitor\FunctionLike\ParamVisitorInterface
   */
  public function addFunction(string $name, array $imports, array $attributes): ParamVisitorInterface;

}
