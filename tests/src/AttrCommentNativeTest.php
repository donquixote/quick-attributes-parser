<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

use Donquixote\QuickAttributes\AttributeCommentParser\AttributeCommentParser;
use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\Tests\Util\TestExportUtil;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 *   See https://github.com/sebastianbergmann/phpunit/pull/4795
 */
class AttrCommentNativeTest extends AttrCommentParserTest {

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
    if (isset($data['namespace'])) {
      $php .= "namespace $data[namespace];\n";
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
    $comment = $data['comment'];
    if (isset($data['class'])) {
      $comment = \preg_replace(
        '@([^\w\\\\]|^)self::@i',
        '$1\\' . $data['class'] . '::',
        $comment);
    }
    $php .= 'return'
      . "\n  $comment"
      . "\n  function () {};"
      . "\n";
    unset($data['attributes']);
    try {
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
        $data['attributes'][] = [
          'name' => $ra->getName(),
          'arguments' => $ra->getArguments(),
        ];
      }
      unset($data['exception']);
    }
    catch (\Throwable $e) {
      $data['exception'] = TestExportUtil::exportException($e);
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
