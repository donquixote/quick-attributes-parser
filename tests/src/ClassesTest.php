<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

use Donquixote\QuickAttributes\AttributeReader\AttributeReader;
use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\FileTokens\FileTokens_Common;
use Donquixote\QuickAttributes\Parser\FileParser;
use Donquixote\QuickAttributes\RawAttributesReader\RawAttributesReader;
use Donquixote\QuickAttributes\Registry\SymbolInfoRegistry;
use Donquixote\QuickAttributes\Tests\Util\TestExportUtil;
use Donquixote\QuickAttributes\Tests\Util\TestUtil;
use Donquixote\QuickAttributes\Value\SymbolHandle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 *   See https://github.com/sebastianbergmann/phpunit/pull/4795
 */
class ClassesTest extends TestCase {

  /**
   * @dataProvider providerTestClasses()
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function testTokenizer(string $shortname): void {
    $file = $this->getClassesDir() . '/' . $shortname . '.php';
    $tokenss = FileTokens_Common::fromFile($file)->getTokenss();
    $tokens = $tokenss->current();
    $n = \count($tokens);
    self::assertSame('{', $tokens[$n - 2]);
    self::assertSame('#', $tokens[$n - 1]);
    $tokenss->next();
    self::assertTrue($tokenss->valid());
    $tokens = $tokenss->current();
    $n = \count($tokens);
    self::assertSame('#', $tokens[$n - 1]);
    $tokenss->next();
    self::assertFalse($tokenss->valid());
  }

  /**
   * @dataProvider providerTestClasses()
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function testParser(string $shortname): void {
    if (\PHP_VERSION_ID >= 80000) {
      self::assertTrue(TRUE, 'Skip test in PHP 8+.');
      return;
    }
    $ymlDir = $this->getYmlDir();
    $file = $this->getClassesDir() . '/' . $shortname . '.php';
    $parser = new FileParser();
    $importss = [];
    $commentss = [];
    try {
      /**
       * @psalm-ignore-var
       * @var \Donquixote\QuickAttributes\Value\SymbolHandle $symbol
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
      $e->setSourceFile($file, \dirname(__DIR__, 2));
      throw $e;
    }
    TestUtil::assertFileContentsYml("$ymlDir/$shortname.imports.yml", $importss);
    $this->shortnameAssertCommentsFile($shortname, $commentss);
  }

  /**
   * @dataProvider providerTestClasses()
   *
   * @throws \ReflectionException
   */
  public function testRegistry(string $shortname): void {
    if (\PHP_VERSION_ID >= 80000) {
      self::assertTrue(TRUE, 'Skip test in PHP 8+.');
      return;
    }
    $ymlDir = $this->getYmlDir();
    $registry = SymbolInfoRegistry::create();
    /** @var array<string, array<string, string>> $importss */
    $importss = Yaml::parseFile("$ymlDir/$shortname.imports.yml");
    foreach ($importss as $symbolId => $imports) {
      $symbol = SymbolHandle::fromId($symbolId);
      self::assertSame($imports, $registry->symbolGetImports($symbol));
    }
    $commentss = $this->shortnameLoadComments($shortname);
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
      \array_keys($importss),
      \array_keys($toplevelNamesMap));
  }

  /**
   * @dataProvider providerTestClasses()
   */
  public function testRawReader(string $shortname): void {
    $ymlDir = $this->getYmlDir();
    $file = "$ymlDir/$shortname.raw-attributes.yml";
    $reader = RawAttributesReader::create();
    /** @psalm-suppress MixedAssignment */
    /** @var array<string, array[]> $orig */
    $orig = (\PHP_VERSION_ID >= 80000)
      ? Yaml::parseFile($file)
      : [];
    $data = [];
    foreach ($this->shortnameGetSymbols($shortname) as $id => $symbol) {
      try {
        $attributes = $reader->read($symbol);
        $data[$id] = TestExportUtil::exportRawAttributes(
          $attributes,
          $orig[$id] ?? []);
      }
      catch (\ReflectionException $e) {
        $data[$id]['exception'] = TestExportUtil::exportException($e);
      }
    }
    TestUtil::assertFileContentsYml($file, $data);
  }

  /**
   * @dataProvider providerTestClasses()
   *
   * @throws \Exception
   */
  public function testReader(string $shortname): void {
    $reader = AttributeReader::create();
    $data = [];
    foreach ($this->shortnameGetSymbols($shortname) as $id => $symbol) {
      $list = $reader->read($symbol);

      if ($list) {
        $instances = $list->createInstances();
        foreach ($instances as $instance) {
          $data[$id][] = TestExportUtil::exportObject($instance);
        }
      }
    }
    TestUtil::assertFileContentsYml(
      $this->getYmlDir() . "/$shortname.instances.yml",
      $data);
  }

  public function testNoOrphanYmlFiles(): void {
    $ymlDir = $this->getYmlDir();

    // Verify that no orphan yml files exist.
    $actualFilesMap = [];
    foreach (\scandir($ymlDir) as $candidate) {
      if (\preg_match('@\.yml$@', $candidate, $m)) {
        $actualFilesMap["$ymlDir/$candidate"] = TRUE;
      }
    }
    \ksort($actualFilesMap);

    $expectedFilesMap = [];
    foreach ($this->getClassShortNames() as $shortname) {
      foreach([
        "$ymlDir/$shortname.imports.yml",
        "$ymlDir/$shortname.comments.yml",
        "$ymlDir/$shortname.raw-attributes.yml",
        "$ymlDir/$shortname.instances.yml",
      ] as $file) {
        $expectedFilesMap[$file] = TRUE;
      }
    }
    \ksort($expectedFilesMap);

    self::assertSame($expectedFilesMap, $actualFilesMap);
  }

  public function providerTestClasses(): \Iterator {
    foreach ($this->getClassShortNames() as $shortname) {
      yield [$shortname];
    }
  }

  /**
   * @param string $shortname
   * @param array<string, string[]> $commentss
   */
  protected function shortnameAssertCommentsFile(string $shortname, array $commentss): void {
    foreach ($commentss as &$comments) {
      foreach ($comments as &$comment) {
        // Trim the line break on the right.
        self::assertStringEndsWith("\n", $comment);
        $comment = \substr($comment, 0, -1);
      }
    }
    TestUtil::assertFileContentsYml(
      $this->getYmlDir() . "/$shortname.comments.yml",
      $commentss);
  }

  /**
   * @param string $shortname
   *
   * @return array<string, string[]>
   */
  protected function shortnameLoadComments(string $shortname): array {
    $ymlDir = $this->getYmlDir();
    // Use *.comments.yml to get a list of symbols that could have attributes.
    /** @var array<string, array<string, string>> $commentss */
    $commentss = Yaml::parseFile("$ymlDir/$shortname.comments.yml");
    foreach ($commentss as &$comments) {
      foreach ($comments as &$comment) {
        $comment .= "\n";
      }
    }
    return $commentss;
  }

  /**
   * @param string $shortname
   *
   * @return array<string, \Donquixote\QuickAttributes\Value\SymbolHandle>
   */
  protected function shortnameGetSymbols(string $shortname): array {
    $ymlDir = $this->getYmlDir();
    // Use *.comments.yml to get a list of symbols that could have attributes.
    /** @var array<string, array<string, string>> $commentss */
    $commentss = Yaml::parseFile("$ymlDir/$shortname.comments.yml");
    $symbols = [];
    foreach ($commentss as $id => $_comments) {
      $symbols[$id] = SymbolHandle::fromId($id);
    }
    return $symbols;
  }

  /**
   * @return string[]
   */
  protected function getClassShortNames(): array {
    $names = [];
    $classesDir = $this->getClassesDir();
    foreach (\scandir($classesDir) as $candidate) {
      if (\preg_match('@^(\w+)\.php$@', $candidate, $m)) {
        $names[] = $m[1];
      }
    }

    return $names;
  }

  private function getClassesDir(): string {
    return __DIR__ . '/Fixture';
  }

  private function getYmlDir(): string {
    return \dirname(__DIR__) . '/fixtures/classes';
  }

}
