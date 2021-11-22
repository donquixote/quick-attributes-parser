<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\RawAttribute;

/**
 * @template-covariant T as object
 */
interface RawAttributeInterface {

  /**
   * @return class-string<T>
   */
  public function getName(): string;

  /**
   * Gets argument values evaluated in current global scope.
   *
   * @return array
   *
   * @throws \ReflectionException
   */
  public function getArguments(): array;

}
