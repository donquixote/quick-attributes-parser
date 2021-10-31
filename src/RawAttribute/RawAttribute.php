<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\RawAttribute;

use Donquixote\QuickAttributes\Util\ArgumentsUtil;

class RawAttribute {

  /**
   * @template TKey as array-key
   * @template T as object
   *
   * @param array<TKey, \Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface> $rawAttributes
   * @param class-string<T>|null $type
   *
   * @return array<TKey, $type is null ? object : T>
   * @throws \ReflectionException
   */
  public static function createInstances(array $rawAttributes, string $type = NULL): array {
    $instances = [];
    if ($type === NULL) {
      foreach ($rawAttributes as $i => $rawAttribute) {
        $instances[$i] = self::createInstance($rawAttribute);
      }
    }
    else {
      foreach ($rawAttributes as $i => $rawAttribute) {
        if (is_a($rawAttribute->getName(), $type, TRUE)) {
          $instances[$i] = self::createInstance($rawAttribute);
        }
      }
    }
    /** @var array<TKey, T> $instances */
    return $instances;
  }

  /**
   * @param \Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface $rawAttribute
   *
   * @return object
   *   Attribute instance.
   *
   * @throws \ReflectionException
   *   Class does not exist, or arguments mismatch.
   */
  public static function createInstance(RawAttributeInterface $rawAttribute): object {
    return self::doCreateInstance(
      $rawAttribute->getName(),
      $rawAttribute->getArguments());
  }

  /**
   * @template T as object
   *
   * @param class-string<T> $class
   * @param array $args
   *
   * @return T
   * @throws \ReflectionException
   */
  private static function doCreateInstance(string $class, array $args): object {
    $mappedArgs = ArgumentsUtil::classMapArgs($class, $args);
    assert(count($args) <= count($mappedArgs));
    try {
      /** @psalm-suppress MixedMethodCall */
      return new $class(...$mappedArgs);
    }
    catch (\RuntimeException $e) {
      throw $e;
    }
    catch (\Throwable $e) {
      throw new \ReflectionException($e->getMessage(), 0, $e);
    }
  }

}
