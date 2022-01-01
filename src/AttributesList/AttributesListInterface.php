<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\AttributesList;

interface AttributesListInterface {

  /**
   * @template T as object
   *
   * @param class-string<T>|null $type
   *
   * @return bool
   */
  public function has(string $type = null): bool;

  /**
   * @template T as object
   *
   * @param class-string<T>|null $type
   *
   * @return int
   */
  public function count(string $type = null): int;

  /**
   * @template T as object
   *
   * @param class-string<T>|null $type
   *
   * @return T[]|object[]
   * @psalm-return ($type is null ? object : T)[]
   *
   * @throws \ReflectionException
   */
  public function createInstances(string $type = null): array;

}
