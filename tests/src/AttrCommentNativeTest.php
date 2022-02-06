<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

use Donquixote\QuickAttributes\Tests\Util\TestArrayUtil;
use Donquixote\QuickAttributes\Tests\Util\TestExportUtil;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 *   See https://github.com/sebastianbergmann/phpunit/pull/4795
 */
class AttrCommentNativeTest extends AttrCommentParserTest {

  /**
   * {@inheritdoc}
   */
  protected function processDataByVersion(array &$data, string $name): void {
    $orig = $data;
    unset($data['attributes.php8']);
    parent::processDataByVersion($data, $name);
    if (\array_key_exists('attributes.php8', $orig)) {
      $data['attributes.php8'] = $orig['attributes.php8'];
    }
    $keys = $this->getKnownKeys();
    TestArrayUtil::normalizeKeys($data, $keys);
  }

  /**
   * {@inheritdoc}
   */
  protected function processData(array &$data, string $name): void {
    if (\PHP_VERSION_ID < 80000) {
      return;
    }
    if (!empty($data['fatal'])) {
      // Evaluating the snippet would lead to fatal error.
      return;
    }
    $php = '';
    $tns = '';
    if (isset($data['namespace'])) {
      $php .= "namespace $data[namespace];\n";
      $tns = $data['namespace'] . '\\';
    }
    foreach ($data['imports'] ?? [] as $alias => $qcn) {
      // Optimize for the more common case where the alias has no space.
      if (false !== $spacepos = \strpos($alias, ' ')) {
        $type = \substr($alias, 0, $spacepos);
        $alias = \substr($alias, $spacepos + 1);
        $php .= "use $type $qcn as $alias;\n";
      }
      else {
        $php .= "use $qcn as $alias;\n";
      }
    }
    $attributes = [];
    try {
      $comment = $data['comment'];
      if (isset($data['class'])) {
        $comment = \preg_replace(
          '@([^\w\\\\]|^)self::@i',
          '$1\\' . $tns . $data['class'] . '::',
          $comment);
      }
      elseif (\preg_match('@([^\w\\\\]|^)self::@i', $comment)) {
        throw new \Exception("Unexpected 'self::' outside of class context.");
      }
      $php .= 'return'
        . "\n  $comment"
        . "\n  function () {};"
        . "\n";
      \set_error_handler(static function (int $code, string $message): bool {
        $core_constants = \get_defined_constants(true)['Core'] ?? [];
        $error_const_names = \preg_grep('@^E_.*@', \array_keys($core_constants));
        $error_constants = \array_intersect_key($core_constants, \array_fill_keys($error_const_names, true));
        $error_const_names_map = \array_flip($error_constants);
        $error_type = $error_const_names_map[$code] ?? $code;
        $message = $error_type . ': '. $message;
        throw new \Exception($message);
      });
      /** @var \Closure $f */
      $f = self::doEval($php);
      $rf = new \ReflectionFunction($f);
      /** @var list<array{name: class-string, arguments: array}> $attributes */
      foreach ($rf->getAttributes() as $ra) {
        $attributes[] = [
          'name' => $ra->getName(),
          'arguments' => $ra->getArguments(),
        ];
      }
      if (($data['attributes'] ?? []) === $attributes) {
        unset($data['attributes.native']);
      }
      else {
        $data['attributes.native'] = $attributes ?: null;
      }
      unset($data['exception.native']);
    }
    catch (\Throwable $e) {
      $data['exception.native'] = TestExportUtil::exportException($e);
      if (isset($data['attributes'])) {
        $data['attributes.native'] = null;
      }
      else {
        unset($data['attributes.native']);
      }
      return;
    }
    finally {
      \restore_error_handler();
    }
  }

  /**
   * @param string $php
   *
   * @return mixed
   */
  private static function doEval(string $php) {
    return eval($php);
  }

}
