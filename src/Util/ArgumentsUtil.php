<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Util;

class ArgumentsUtil {

  /**
   * @template T as object
   *
   * @param class-string<T> $class
   * @param array $args
   *
   * @return array
   * @throws \ReflectionException
   */
  public static function classMapArgs(string $class, array $args): array {
    $rc = new \ReflectionClass($class);
    $ctr = $rc->getConstructor();
    if ($ctr) {
      if ($params = $ctr->getParameters()) {
        return self::mapNamedArgs($params, $args);
      }
    }
    return self::mapNamedArgs([], $args);
  }

  /**
   * @param \ReflectionParameter[] $params
   * @param array $args
   *
   * @return list<mixed>
   *
   * @throws \ReflectionException
   */
  public static function mapNamedArgs(array $params, array $args): array {
    $map = [];
    $lastParam = \end($params);
    if ($lastParam && $lastParam->isVariadic()) {
      \array_pop($params);
    }
    foreach ($params as $i => $param) {
      $map[$param->getName()] = $i;
    }
    /** @var array<int, mixed> $mappedArgs */
    $mappedArgs = [];
    $named = false;
    /** @var mixed $v */
    foreach ($args as $k => $v) {
      if (!\is_string($k)) {
        if ($named) {
          throw new \ReflectionException('Cannot have positional arguments after named arguments.');
        }
        /** @psalm-suppress MixedAssignment */
        $mappedArgs[] = $v;
      }
      else {
        $named = true;
        $index = $map[$k] ?? null;
        if ($index === null) {
          throw new \ReflectionException("Unknown argument name '$k'.");
        }
        if (\array_key_exists($index, $mappedArgs)) {
          throw new \ReflectionException('Cannot overwrite argument.');
        }
        /** @psalm-suppress MixedAssignment */
        $mappedArgs[$index] = $v;
      }
    }
    foreach ($params as $i => $param) {
      if (!\array_key_exists($i, $mappedArgs)) {
        if (!$param->isOptional()) {
          $rf = $param->getDeclaringFunction();
          $fname = $rf->getName() . '()';
          if ($rf instanceof \ReflectionMethod) {
            $fname = $rf->getDeclaringClass()->getName() . '::' . $fname;
          }
          throw new \ReflectionException(\vsprintf(
            "Missing argument %s for %s.",
            [$i, $fname]));
        }
        /** @psalm-suppress MixedAssignment */
        try {
          $mappedArgs[$i] = $param->getDefaultValue();
        }
        catch (\ReflectionException $e) {
          throw new \ReflectionException(
            \vsprintf('Problem getting default value for argument #%s $%s', [
              $i,
              $param->getName(),
            ]),
            0,
            $e);
        }
      }
    }
    /** @var list<mixed> $mappedArgs */
    \ksort($mappedArgs);
    return $mappedArgs;
  }

}
