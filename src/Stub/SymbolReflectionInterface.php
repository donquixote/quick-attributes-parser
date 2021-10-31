<?php /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Stub;

interface SymbolReflectionInterface extends \Reflector{

  /**
   * Returns an array of constant attributes.
   *
   * @template T as object
   *
   * @param class-string<T>|null $name
   *   Name of an attribute class.
   * @param int $flags
   *   Сriteria by which the attribute is searched.
   *
   * @return list<\ReflectionAttribute<T>>
   * @psalm-return list<\ReflectionAttribute<($name is null ? object : T)>>
   *
   * @noinspection PhpUndefinedClassInspection
   */
  public function getAttributes(string $name = null, int $flags = 0): array;

}
