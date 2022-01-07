<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\ClassLike;

interface ClassInfoInterface extends GlobalSymbolInfoInterface, ClassBodyInfoInterface {

  /**
   * Gets the qualified class name.
   *
   * @return class-string
   */
  public function getName(): string;

}
