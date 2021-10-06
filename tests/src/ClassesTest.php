<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\Parser\FileParser;
use Donquixote\QuickAttributes\Value\SymbolHandle;
use Donquixote\QuickAttributes\Registry\SymbolInfoRegistryPhp7;
use Donquixote\QuickAttributes\Tests\Fixture\CAdvanced;
use Donquixote\QuickAttributes\Tests\Util\TestUtil;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

class ClassesTest extends TestCase {

  /**
   * @dataProvider providerTestClasses()
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function testParser(string $file, string $class, string $importsYmlFile, string $commentsYmlFile) {
    $parser = new FileParser();
    $rc = new \ReflectionClass(CAdvanced::class);
    $file = $rc->getFileName();
    $importss = [];
    $commentss = [];
    try {
      /**
       * @var \Donquixote\QuickAttributes\Value\SymbolHandle $symbol
       * @var \Donquixote\QuickAttributes\Value\RawSymbolInfo $info
       */
      foreach ($parser->parseFile($file) as $symbol => $info) {
        $toplevel = $symbol->getTopLevel();
        if ($toplevel === $symbol) {
          $importss[(string) $symbol] = $info->getImports();
        }
        else {
          // Inner symbol must have same imports as top-level symbol.
          self::assertNull($info->getImports(), (string) $symbol);
        }
        $commentss[(string) $symbol] = $info->getAttributeComments();
      }
    }
    catch (ParserException $e) {
      $e->setSourceFile($file, dirname(__DIR__, 2));
      throw $e;
    }
    TestUtil::assertFileContentsYml($importsYmlFile, $importss);
    TestUtil::assertFileContentsYml($commentsYmlFile, $commentss);
  }

  /**
   * @dataProvider providerTestClasses()
   *
   * @throws \ReflectionException
   */
  public function testRegistry(string $file, string $class, string $importsYmlFile, string $commentsYmlFile) {
    $registry = SymbolInfoRegistryPhp7::create();
    $importss = Yaml::parseFile($importsYmlFile);
    foreach ($importss as $symbolId => $imports) {
      $symbol = SymbolHandle::fromId($symbolId);
      self::assertSame($imports, $registry->symbolGetImports($symbol));
    }
    $commentss = Yaml::parseFile($commentsYmlFile);
    $toplevelNamesMap = [];
    foreach ($commentss as $symbolId => $comments) {
      $symbol = SymbolHandle::fromId($symbolId);
      $toplevel = $symbol->getTopLevel();
      if ($toplevel === $symbol) {
        $toplevelNamesMap[(string) $symbol->getTopLevel()] = TRUE;
      }
      else {
        self::assertArrayHasKey((string) $toplevel, $toplevelNamesMap);
      }
      self::assertSame($comments, $registry->symbolGetAttributesComments($symbol));
    }
    self::assertSame(
      array_keys($importss),
      array_keys($toplevelNamesMap));
  }

  public function testNoOrphanYmlFiles(): void {
    $ymlDir = dirname(__DIR__) . '/fixtures/classes';
    $argss = $this->providerTestClasses();

    // Verify that no orphan yml files exist.
    $orphanFiles = [];
    foreach (scandir($ymlDir) as $candidate) {
      if (preg_match('@^(\w+)\.(\w+)\.yml$@', $candidate, $m)) {
        [, $name, $type] = $m;
        if (!isset($argss[$name])) {
          $orphanFiles["$ymlDir/$candidate"] = "Found yml file for unknown name '$name'";
        }
        if (!in_array($type, ['imports', 'comments'])) {
          $orphanFiles["$ymlDir/$candidate"] = "Found yml file for unknown type '$type'";
        }
      }
    }

    self::assertEmpty($orphanFiles);
  }

  public function providerTestClasses(): array {
    $argss = [];
    $classesDir = __DIR__ . '/Fixture';
    $ymlDir = dirname(__DIR__) . '/fixtures/classes';
    foreach (scandir($classesDir) as $candidate) {
      if (preg_match('@^(\w+)\.php$@', $candidate, $m)) {
        $shortname = $m[1];
        $argss[$shortname] = [
          $classesDir . '/' . $candidate,
          __NAMESPACE__ . '\\Fixture\\' . $shortname,
          $ymlDir . '/' . $shortname . '.imports.yml',
          $ymlDir . '/' . $shortname . '.comments.yml',
        ];
      }
    }

    return $argss;
  }

}
