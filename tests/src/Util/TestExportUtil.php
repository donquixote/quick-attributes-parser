<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests\Util;

use Donquixote\QuickAttributes\RawAttribute\RawAttribute_Eval;
use Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface;

class TestExportUtil {

  public static function exportObject(object $object): array {
    $export = [];
    $export['class'] = get_class($object);
    /** @psalm-suppress MixedAssignment */
    foreach ((array) $object as $k => $v) {
      $export['$' . $k] = $v;
    }
    return $export;
  }

  /**
   * @param \Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface[] $attributes
   * @param array[] $orig
   *
   * @return array
   */
  public static function exportRawAttributes(array $attributes, array $orig = []): array {
    $export = [];
    foreach ($attributes as $delta => $attribute) {
      $export[$delta] = self::exportRawAttribute($attribute, $orig[$delta] ?? []);
    }
    return $export;
  }

  /**
   * @param \Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface $attribute
   * @param array $orig
   *
   * @return array
   */
  public static function exportRawAttribute(RawAttributeInterface $attribute, array $orig = []): array {
    $record = [
      'name' => $attribute->getName(),
    ];
    try {
      $record['arguments'] = $attribute->getArguments();
    }
    catch (\ReflectionException $e) {
      $record['exception'] = self::exportException($e);
    }
    return $record;
  }

  /**
   * @param \Throwable $e
   *
   * @return array
   */
  public static function exportException(\Throwable $e): array {
    $message = $e->getMessage();
    $basedir = \dirname(__DIR__, 3);
    $message = \str_replace($basedir, '[..]', $message);
    $message = \preg_replace('@line \d+@', 'line **', $message);
    $ret = [
      'class' => get_class($e),
      'message' => $message,
    ];
    if ($ePrev = $e->getPrevious()) {
      $ret['previous'] = self::exportException($ePrev);
    }
    return $ret;
  }

}