<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

use Donquixote\QuickAttributes\AttributeReader\AttributeReader_Fallback;
use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\Parser\FileParser;
use Donquixote\QuickAttributes\Registry\SymbolInfoRegistry;
use Donquixote\QuickAttributes\Tests\Util\TestUtil;
use Donquixote\QuickAttributes\Value\SymbolHandle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

class ClassesTest extends TestCase {

  /**
   * @dataProvider providerTestClasses()
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function testParser(string $shortname) {
    $ymlDir = $this->getYmlDir();
    $file = $this->getClassesDir() . '/' . $shortname . '.php';
    $parser = new FileParser();
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
    TestUtil::assertFileContentsYml("$ymlDir/$shortname.imports.yml", $importss);
    TestUtil::assertFileContentsYml("$ymlDir/$shortname.comments.yml", $commentss);
  }

  /**
   * @dataProvider providerTestClasses()
   *
   * @throws \ReflectionException
   */
  public function testRegistry(string $shortname) {
    $ymlDir = $this->getYmlDir();
    $registry = SymbolInfoRegistry::create();
    $importss = Yaml::parseFile("$ymlDir/$shortname.imports.yml");
    foreach ($importss as $symbolId => $imports) {
      $symbol = SymbolHandle::fromId($symbolId);
      self::assertSame($imports, $registry->symbolGetImports($symbol));
    }
    $commentss = Yaml::parseFile("$ymlDir/$shortname.comments.yml");
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

  /**
   * @dataProvider providerTestClasses()
   *
   * @throws \Exception
   */
  public function testReader(string $shortname) {
    $ymlDir = $this->getYmlDir();
    $reader = AttributeReader_Fallback::create();
    $commentss = Yaml::parseFile("$ymlDir/$shortname.comments.yml");
    $stats = [];
    foreach ($commentss as $id => $comments) {
      $symbol = SymbolHandle::fromId($id);
      $list = $reader->read($symbol);
      if ($list) {
        $instances = $list->createInstances();
        foreach ($instances as $instance) {
          $stats[$id][] = serialize($instance);
        }
      }
    }
    echo "\n", Yaml::dump($stats), "\n";
  }

  public function testNoOrphanYmlFiles(): void {
    $ymlDir = $this->getYmlDir();

    // Verify that no orphan yml files exist.
    $actualFilesMap = [];
    foreach (scandir($ymlDir) as $candidate) {
      if (preg_match('@\.yml$@', $candidate, $m)) {
        $actualFilesMap["$ymlDir/$candidate"] = TRUE;
      }
    }
    ksort($actualFilesMap);

    $expectedFilesMap = [];
    foreach ($this->getClassShortNames() as $shortname) {
      foreach([
        "$ymlDir/$shortname.imports.yml",
        "$ymlDir/$shortname.comments.yml",
      ] as $file) {
        $expectedFilesMap[$file] = TRUE;
      }
    }
    ksort($expectedFilesMap);

    self::assertSame($expectedFilesMap, $actualFilesMap);
  }

  public function providerTestClasses(): \Iterator {
    foreach ($this->getClassShortNames() as $shortname) {
      yield [$shortname];
    }
  }

  /**
   * @return string[]
   */
  protected function getClassShortNames(): array {
    $names = [];
    $classesDir = $this->getClassesDir();
    foreach (scandir($classesDir) as $candidate) {
      if (preg_match('@^(\w+)\.php$@', $candidate, $m)) {
        $names[] = $m[1];
      }
    }

    return $names;
  }

  private function getClassesDir(): string {
    return __DIR__ . '/Fixture';
  }

  private function getYmlDir(): string {
    return dirname(__DIR__) . '/fixtures/classes';
  }

  private function getNamespace(): string {
    return __NAMESPACE__ . '\\Fixture';
  }

}
