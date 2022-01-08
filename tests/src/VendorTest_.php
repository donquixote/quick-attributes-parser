<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

use Donquixote\QuickAttributes\Exception\PhpVersionException;
use Donquixote\QuickAttributes\Exception\UnsupportedSyntaxException;
use Donquixote\QuickAttributes\Parser\FileParser;
use Donquixote\QuickAttributes\SymbolInfo\ClassInfo;
use Donquixote\QuickAttributes\SymbolInfo\FileInfo;
use Donquixote\QuickAttributes\SymbolVisitor\SymbolVisitor_NoOp;
use Infection\ExtensionInstaller\Plugin;
use PhpBench\Attributes\AbstractMethodsAttribute;
use PhpBench\Attributes\AfterClassMethods;
use PhpBench\Attributes\AfterMethods;
use PhpBench\Attributes\BeforeClassMethods;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Attributes\RetryThreshold;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\DependencyInjection\AddConsoleCommandPass;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Event\ConsoleSignalEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\EventListener\ErrorListener;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Contracts\Service\Attribute\SubscribedService;

/**
 * This test does not run by default, but can be executed manually.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *   See https://github.com/sebastianbergmann/phpunit/pull/4795#
 * @psalm-suppress UnusedClass
 */
class VendorTest_ extends TestCase {

  private const BAD_PATHS = [
    'vendor/amphp/amp/lib/functions.php',
    'vendor/phpbench/phpbench/lib/Template/Expression/Printer/SkipTemplate.php',
    'vendor/composer/package-versions-deprecated/src/PackageVersions',
  ];

  /** @psalm-suppress MissingDependency */
  private const BAD_CLASSES = [
    SubscribedService::class,
    AsciiSlugger::class,
    AsCommand::class,
    AddConsoleCommandPass::class,
    ConsoleEvent::class,
    ConsoleCommandEvent::class,
    ConsoleErrorEvent::class,
    ConsoleSignalEvent::class,
    ConsoleTerminateEvent::class,
    ErrorListener::class,
    AbstractMethodsAttribute::class,
    AfterClassMethods::class,
    BeforeClassMethods::class,
    AfterMethods::class,
    BeforeMethods::class,
    Iterations::class,
    ParamProviders::class,
    RetryThreshold::class,
    Revs::class,
    Warmup::class,
    Plugin::class,
  ];

  /**
   * @dataProvider providerTestClassFile()
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   * @throws \ReflectionException
   */
  public function testFileInfo(string $file): void {
    foreach (FileInfo::fromFile($file)->readElements() as $element) {
      if ($element instanceof ClassInfo) {
        /** @var class-string $class */
        $class = $element->getName();
        $rc = new \ReflectionClass($class);
        $readerMethodNames = [];
        foreach ($element->readMethods() as $method) {
          $readerMethodNames[$method->getName()] = true;
        }
        $reflectionMethodNames = [];
        foreach ($rc->getMethods() as $rm) {
          if ($rm->getDeclaringClass()->getName() !== $class) {
            continue;
          }
          if ($rm->getFileName() !== $rc->getFileName()) {
            continue;
          }
          $reflectionMethodNames[$rm->getName()] = true;
        }
        if ($reflectionMethodNames !== $readerMethodNames) {
          # self::fail($file);
        }
        self::assertSame($reflectionMethodNames, $readerMethodNames);
      }
    }
    self::assertTrue(true);
  }

  /**
   * @dataProvider providerTestClassFile()
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function testClassFileParser(string $file): void {
    if (\PHP_VERSION_ID >= 80000) {
      self::assertTrue(true);
      return;
    }
    $parser = FileParser::create();
    try {
      /** @noinspection PhpUnusedLocalVariableInspection */
      foreach ($parser->parseFile($file, new SymbolVisitor_NoOp()) as $_) {}
    }
    catch (PhpVersionException $e) {
      // Ignore language features from PHP 8.
      $this->assertTrue(true);
      return;
    }
    catch (UnsupportedSyntaxException $e) {
      // Ignore unsupported syntax, e.g. nested namespaces.
      $this->assertTrue(true);
      return;
    }
    // All good.
    self::assertTrue(true);
  }

  /**
   * @return \Iterator<array{string}>
   */
  public function providerTestClassFile(): \Iterator {
    /**
     * @var array<string, list<string>> $nsdirs
     */
    $nsdirs = require \dirname(__DIR__, 2) . '/vendor/composer/autoload_psr4.php';
    $troot = \dirname(__DIR__, 2) . '/';
    $ltroot = \strlen($troot);
    foreach ($nsdirs as $ns => $dirs) {
      $ns = \rtrim($ns, '\\');
      $tns = ($ns === '') ? '' : ($ns . '\\');
      foreach ($dirs as $dir) {
        if (\strpos($dir, $troot) !== 0) {
          continue;
        }
        if (!\is_dir($dir)) {
          continue;
        }
        $reldir = \substr($dir, $ltroot);
        yield from $this->nsdirRecursive(
          $troot,
          \rtrim($reldir, '/'),
          $tns);
      }
    }
  }

  /**
   * @param string $troot
   *   Project root terminated with '/'.
   * @param string $reldir
   *   Directory relative to $troot.
   * @param string $tns
   *   Terminated namespace.
   *
   * @return \Iterator<array{string}>
   */
  private function nsdirRecursive(string $troot, string $reldir, string $tns): \Iterator {
    $badClassesMap = \array_fill_keys(self::BAD_CLASSES, true);
    $badPathsMap = \array_fill_keys(self::BAD_PATHS, true);
    if (isset($badPathsMap[$reldir])) {
      return;
    }
    foreach (\scandir($troot . $reldir) as $candidate) {
      $relpath = $reldir . '/' . $candidate;
      if (\preg_match('@^\w+$@', $candidate)) {
        if (\is_dir($troot . $relpath)) {
          yield from $this->nsdirRecursive($troot, $relpath, $tns . $candidate . '\\');
        }
      }
      elseif (\preg_match('@^(\w+)\.php$@', $candidate, $m)) {
        if (isset($badPathsMap[$relpath])) {
          continue;
        }
        if (isset($badClassesMap[$tns . $m[1]])) {
          continue;
        }
        if (\is_file($troot . $relpath)) {
          yield [$relpath];
        }
      }
    }
  }

}
