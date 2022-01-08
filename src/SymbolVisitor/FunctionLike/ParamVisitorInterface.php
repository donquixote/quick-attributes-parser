<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolVisitor\FunctionLike;

interface ParamVisitorInterface {

  /**
   * @param string $name
   * @param list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface> $attributes
   */
  public function addParameter(string $name, array $attributes): void;

  public function markAsComplete(): void;

}
