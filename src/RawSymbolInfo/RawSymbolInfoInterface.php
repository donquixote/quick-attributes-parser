<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\RawSymbolInfo;

interface RawSymbolInfoInterface {

  /**
   * @return list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface>
   *
   * @throws \ReflectionException
   *   Failure to read attributes.
   */
  public function getAttributes(): array;

  /**
   * @return array<string, string>|null
   *   The imports, if this is a top-level symbol.
   *   NULL, if this is an "inner" symbol.
   */
  public function getImports(): ?array;

}
