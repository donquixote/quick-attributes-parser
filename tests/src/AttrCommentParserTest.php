<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

use Donquixote\QuickAttributes\AttributeCommentParser\AttributeCommentParser;
use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\Tests\Util\TestExportUtil;
use Donquixote\QuickAttributes\Tests\Util\TestUtil;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 *   See https://github.com/sebastianbergmann/phpunit/pull/4795
 */
class AttrCommentParserTest extends TestCase {

  /**
   * @param string $name
   *
   * @dataProvider providerTestAttrCommentParser()
   */
  public function testAttrCommentParser(string $name): void {
    $file = $this->getYmlDir() . '/' . $name . '.yml';
    /** @var array $data */
    $data = Yaml::parseFile($file);
    if (PHP_VERSION_ID < 80000) {
      $this->processData($data);
    }
    else {
      $this->processPhp8($data);
    }
    TestUtil::assertFileContentsYml($file, $data);
  }

  private function processData(array &$data): void {

    // Normalize and filter array keys.
    $map = array_fill_keys(['comment', 'namespace', 'imports', 'class'], NULL);
    $data = array_intersect_key($data, $map);
    $data = array_filter(array_replace($map, $data));

    $parser = new AttributeCommentParser();
    /** @psalm-suppress MixedArgument */
    $parser = $parser->withContext(
      $data['namespace'] ?? NULL,
      $data['imports'] ?? [],
      $data['class'] ?? NULL);

    try {
      /** @psalm-suppress MixedArgument, PossiblyUndefinedStringArrayOffset */
      $attributes = $parser->parse($data['comment']);
    }
    catch (ParserException $e) {
      $data['exception'] = TestExportUtil::exportException($e);
      return;
    }

    $data['attributes'] = TestExportUtil::exportRawAttributes($attributes);
  }

  private function processPhp8(array &$data): void {
    if (PHP_VERSION_ID < 80000) {
      return;
    }
    $php = '';
    if (isset($data['namespace'])) {
      $php .= "namespace $data[namespace];\n";
    }
    /**
     * @var string $alias
     * @var string $qcn
     */
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
    /**
     * @var string $comment
     * @psalm-suppress PossiblyUndefinedStringArrayOffset
     */
    $comment = $data['comment'];
    if (isset($data['class'])) {
      /** @psalm-suppress MixedOperand */
      $comment = \preg_replace(
        '@([^\w\\\\]|^)self::@i',
        '$1\\' . $data['class'] . '::',
        $comment);
    }
    $php .= 'return'
      . "\n  $comment"
      . "\n  function () {};"
      . "\n";
    /** @psalm-suppress MixedAssignment */
    $f = self::doEval($php);
    /** @var \Closure $f */
    try {
      $rf = new \ReflectionFunction($f);
    }
    catch (\ReflectionException $e) {
      throw new \RuntimeException($e->getMessage(), 0, $e);
    }
    $attributes = [];
    /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
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

  /**
   * @return iterable<int, array{string}>
   */
  public function providerTestAttrCommentParser(): iterable {
    $ymlDir = $this->getYmlDir();
    foreach (scandir($ymlDir) as $candidate) {
      if (preg_match('@^(\w+(?:[\.\-]\w+)*)\.yml$@', $candidate, $m)) {
        yield [$m[1]];
      }
    }
  }

  private function getYmlDir(): string {
    return dirname(__DIR__) . '/fixtures/attr-comments';
  }

}
