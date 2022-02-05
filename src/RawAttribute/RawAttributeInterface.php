<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\RawAttribute;

use Donquixote\QuickAttributes\ValueExpression\ArgumentsInterface;

/**
 * @template-covariant T as object
 */
interface RawAttributeInterface extends ArgumentsInterface {

  /**
   * @return class-string<T>
   */
  public function getName(): string;

}
