<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

use Donquixote\QuickAttributes\AttributeCommentParser\AttributeCommentParser;
use Donquixote\QuickAttributes\Exception\ParserException;
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
 *   exception?: array
 * }
 *
 * @template-extends YmlTestBase<_AttrCommentsYaml>
 */
class AttrCommentParserTest extends YmlTestBase {

  /**
   * {@inheritdoc}
   */
  protected function processData(array &$data, string $name): void {
    if (PHP_VERSION_ID < 80000) {
      $this->processPhp7($data);
    }
    else {
      $this->processPhp8($data);
    }
  }

  /**
   * @psalm-param _AttrCommentsYaml $data
   */
  private function processPhp7(array &$data): void {

    // Normalize and filter array keys.
    $map = array_fill_keys(['comment', 'namespace', 'imports', 'class'], NULL);

    /** @var _AttrCommentsYaml $data */
    $data = array_intersect_key($data, $map);

    /** @var _AttrCommentsYaml $data */
    $data = array_filter(array_replace($map, $data));

    $parser = new AttributeCommentParser();
    $parser = $parser->withContext(
      $data['namespace'] ?? NULL,
      $data['imports'] ?? [],
      $data['class'] ?? NULL);

    try {
      $attributes = $parser->parse($data['comment'] . "\n");
    }
    catch (ParserException $e) {
      $data['exception'] = TestExportUtil::exportException($e);
      return;
    }

    $data['attributes'] = TestExportUtil::exportRawAttributes($attributes);
  }

  /**
   * @psalm-param _AttrCommentsYaml $data
   */
  private function processPhp8(array &$data): void {
    if (PHP_VERSION_ID < 80000) {
      return;
    }
    $php = '';
    if (isset($data['namespace'])) {
      $php .= "namespace $data[namespace];\n";
    }
    foreach ($data['imports'] ?? [] as $alias => $qcn) {
      // Optimize for the more common case where the alias has no space.
      if (FALSE !== $spacepos = strpos($alias, ' ')) {
        $type = substr($alias, 0, $spacepos);
        $alias = substr($alias, $spacepos + 1);
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
    /** @var \Closure $f */
    $f = self::doEval($php);
    try {
      $rf = new \ReflectionFunction($f);
    }
    catch (\ReflectionException $e) {
      throw new \RuntimeException($e->getMessage(), 0, $e);
    }
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
    if ($attributes !== ($data['attributes'] ?? [])) {
      $data['attributes.php8'] = $attributes;
    }
    else {
      unset($data['attributes.php8']);
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
