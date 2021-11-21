<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

use Donquixote\QuickAttributes\AttributeCommentParser\AttributeCommentParser;
use Donquixote\QuickAttributes\Tests\Util\TestArrayUtil;
use Donquixote\QuickAttributes\Tests\Util\TestExportUtil;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 *   See https://github.com/sebastianbergmann/phpunit/pull/4795
 *
 * @psalm-type _AttrCommentsYaml=array{
 *   comment: string,
 *   namespace?: string,
 *   imports?: array<string, string>,
 *   class?: class-string,
 *   attributes?: list<array{
 *     name: class-string,
 *     arguments?: array,
 *     exception?: array,
 *   }>,
 *   'attributes.php8'?: list<array{
 *     name: class-string,
 *     arguments?: array,
 *     exception?: array,
 *   }>,
 *   exception?: array,
 *   'exception.php8'?: array,
 *   mismatch?: true,
 * }
 *
 * @template-extends YmlTestBase<_AttrCommentsYaml>
 */
class AttrCommentParserTest extends YmlTestBase {

  /**
   * {@inheritdoc}
   */
  protected function processData(array &$data, string $name): void {
    if (\PHP_VERSION_ID < 80000) {
      $this->processPhp7($data);
    }
    else {
      $this->processPhp8($data);
    }
    TestArrayUtil::normalizeKeys(
      $data,
      [
        'comment',
        'namespace',
        'imports',
        'class',
        'fatal',
        'attributes',
        'exception',
        'attributes.php8',
        'exception.php8',
        'mismatch',
      ]);
  }

  /**
   * @psalm-param _AttrCommentsYaml $data
   */
  private function processPhp7(array &$data): void {

    $parser = new AttributeCommentParser();
    $parser = $parser->withContext(
      $data['namespace'] ?? NULL,
      $data['imports'] ?? [],
      $data['class'] ?? NULL);

    try {
      $attributes = $parser->parse($data['comment'] . "\n");
    }
    catch (\Throwable $e) {
      unset($data['attributes']);
      $data['exception'] = TestExportUtil::exportException($e);
      return;
    }

    unset($data['exception']);
    $data['attributes'] = TestExportUtil::exportRawAttributes($attributes);
  }

  /**
   * @psalm-param _AttrCommentsYaml $data
   */
  private function processPhp8(array &$data): void {
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
      if (FALSE !== $spacepos = \strpos($alias, ' ')) {
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
      $attributes = [];
      /**
       * @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection
       * @var \ReflectionAttribute $ra
       */
      foreach ($rf->getAttributes() as $ra) {
        /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
        $attributes[] = [
          'name' => $ra->getName(),
          'arguments' => $ra->getArguments(),
        ];
      }
    }
    catch (\Throwable $e) {
      $data['exception.php8'] = TestExportUtil::exportException($e);
      unset($data['attributes.php8']);
      if (!isset($data['exception'])) {
        $data['mismatch'] = TRUE;
      }
      else {
        unset($data['mismatch']);
      }
      return;
    }
    finally {
      \restore_error_handler();
    }
    unset($data['exception.php8']);
    /**
     * @noinspection PhpUndefinedVariableInspection
     *   See https://youtrack.jetbrains.com/issue/WI-25588.
     */
    if ($attributes !== ($data['attributes'] ?? [])) {
      $data['attributes.php8'] = $attributes;
      $data['mismatch'] = true;
    }
    else {
      unset($data['attributes.php8']);
      unset($data['mismatch']);
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

  protected function getYmlSubdir(): string {
    return 'attr-comments';
  }

}
