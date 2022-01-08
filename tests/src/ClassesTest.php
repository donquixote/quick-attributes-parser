<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

use Donquixote\QuickAttributes\ClassFileFinder\ClassFileFinder_ComposerAutoload;
use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\FileTokens\FileTokens_Common;
use Donquixote\QuickAttributes\Parser\FileParser;
use Donquixote\QuickAttributes\Registry\ClassInfoFinder;
use Donquixote\QuickAttributes\SymbolInfo\FileInfo;
use Donquixote\QuickAttributes\SymbolInfo\MethodInfo;
use Donquixote\QuickAttributes\SymbolVisitor\SymbolVisitor_CollectInfo;
use Donquixote\QuickAttributes\Tests\Fixture\CMinimal;
use Donquixote\QuickAttributes\Tests\Util\TestExportUtil;
use Donquixote\QuickAttributes\Tests\Util\TestUtil;
use PHPUnit\Framework\TestCase;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 *   See https://github.com/sebastianbergmann/phpunit/pull/4795
 *
 * @psalm-type _RawAttributeArray=array{
 *   name: class-string,
 *   arguments: mixed[],
 * }
 */
class ClassesTest extends TestCase {

  /**
   * @dataProvider providerTestClasses()
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function testTokenizer(string $shortname): void {
    $file = $this->getClassesDir() . '/' . $shortname . '.php';
    $fileTokens = FileTokens_Common::fromFile($file);
    $head = $fileTokens->getClassFileHead();
    $all = $fileTokens->getAll();
    self::assertSame('#', $all[\count($all) - 1]);
    if ($head !== null) {
      self::assertSame('{', $head[\count($head) - 2]);
      self::assertSame('#', $head[\count($head) - 1]);
    }
  }

  /**
   * @dataProvider providerTestClasses()
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function testParser(string $shortname): void {
    if (\PHP_VERSION_ID >= 80000) {
      self::assertTrue(true, 'Skip test in PHP 8+.');
      return;
    }
    $ymlDir = $this->getYmlDir();
    $file = $this->getClassesDir() . '/' . $shortname . '.php';
    $parser = FileParser::create();
    $visitor = new SymbolVisitor_CollectInfo();
    try {
      /** @noinspection PhpUnusedLocalVariableInspection */
      foreach ($parser->parseFile($file, $visitor) as $_) {}
    }
    catch (ParserException $e) {
      $e->setSourceFile($file, \dirname(__DIR__, 2));
      throw $e;
    }
    $importss = $visitor->getImportss();
    TestUtil::assertFileContentsYml("$ymlDir/$shortname.imports.yml", $importss);
  }

  /**
   * @dataProvider providerTestClasses()
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function testClassInfoFinder(string $shortname): void {
    /** @var class-string $class */
    $class = $this->getClassesNamespace() . '\\' . $shortname;
    $file = ClassFileFinder_ComposerAutoload::create()->find($class);
    self::assertNotNull($file);
    $finder = ClassInfoFinder::create();
    $fileInfo = FileInfo::fromFile($file);
    $found = false;
    foreach ($fileInfo->readClasses() as $c0) {
      self::assertSame($class, $c0->getName());
      $found = true;
    }
    self::assertTrue($found);
    $classInfo = $finder->findClass($class);
    self::assertNotNull($classInfo, "Class $class not found.");
    /** @var array<string, list<_RawAttributeArray>> $orig */
    $data = [];
    $data[$classInfo->getId()] = TestExportUtil::exportRawAttributes( $classInfo->getAttributes());
    foreach ($classInfo->readMembers() as $member) {
      $data[$member->getId()] = TestExportUtil::exportRawAttributes( $member->getAttributes());
      if ($member instanceof MethodInfo) {
        foreach ($member->readParameters() as $parameter) {
          $data[$parameter->getId()] = TestExportUtil::exportRawAttributes( $parameter->getAttributes());
        }
      }
    }
    $ymlDir = $this->getYmlDir();
    $ymlfile = "$ymlDir/$shortname.raw-attributes.yml";
    TestUtil::assertFileContentsYml($ymlfile, $data);
  }

  public function testNoOrphanYmlFiles(): void {
    $ymlDir = $this->getYmlDir();

    // Verify that no orphan yml files exist.
    $actualFilesMap = [];
    foreach (\scandir($ymlDir) as $candidate) {
      if (\preg_match('@\.yml$@', $candidate)) {
        $actualFilesMap["$ymlDir/$candidate"] = true;
      }
    }
    \ksort($actualFilesMap);

    $expectedFilesMap = [];
    foreach ($this->getClassShortNames() as $shortname) {
      foreach([
        "$ymlDir/$shortname.imports.yml",
        "$ymlDir/$shortname.raw-attributes.yml",
        "$ymlDir/$shortname.instances.yml",
      ] as $file) {
        $expectedFilesMap[$file] = true;
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

  /**
   * @return string
   */
  private function getClassesNamespace(): string {
    return \substr(CMinimal::class, 0, -9);
  }

  private function getClassesDir(): string {
    return __DIR__ . '/Fixture';
  }

  private function getYmlDir(): string {
    return \dirname(__DIR__) . '/fixtures/classes';
  }

}
