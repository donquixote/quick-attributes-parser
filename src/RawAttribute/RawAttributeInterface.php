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
   * @return array
   *
   * @throws \ReflectionException
   */
  public function getArguments(): array;

}
