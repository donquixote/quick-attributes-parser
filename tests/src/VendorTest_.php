<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

use Donquixote\QuickAttributes\Exception\PhpVersionException;
use Donquixote\QuickAttributes\Exception\UnsupportedSyntaxException;
use Donquixote\QuickAttributes\Parser\FileParser;
use PHPUnit\Framework\TestCase;

/**
 * This test does not run by default, but can be executed manually.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 *   See https://github.com/sebastianbergmann/phpunit/pull/4795#
 */
class VendorTest_ extends TestCase {

  /**
   * @dataProvider providerTestClassFile()
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function testClassFileParser(string $file): void {
    if (PHP_VERSION_ID >= 80000) {
      self::assertTrue(true);
      return;
    }
    $parser = new FileParser();
    try {
      /** @noinspection PhpUnusedLocalVariableInspection */
      foreach ($parser->parseFile($file) as $_) {}
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
    $nsdirs = require dirname(__DIR__, 2) . '/vendor/composer/autoload_psr4.php';
    $troot = dirname(__DIR__, 2) . '/';
    $ltroot = \strlen($troot);
    foreach ($nsdirs as $dirs) {
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
          rtrim($reldir, '/'));
      }
    }
  }

  /**
   * @param string $troot
   *   Project root terminated with '/'.
   * @param string $reldir
   *   Directory relative to $troot.
   *
   * @return \Iterator<array{string}>
   */
  private function nsdirRecursive(string $troot, string $reldir): \Iterator {
    foreach (\scandir($troot . $reldir) as $candidate) {
      $relpath = $reldir . '/' . $candidate;
      if (\preg_match('@^\w+$@', $candidate)) {
        if (\is_dir($troot . $relpath)) {
          yield from $this->nsdirRecursive($troot, $relpath);
        }
      }
      elseif (\preg_match('@^\w+\.php$@', $candidate)) {
        if (\is_file($troot . $relpath)) {
          yield [$relpath];
        }
      }
    }
  }

}
