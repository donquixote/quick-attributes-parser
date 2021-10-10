<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

use Donquixote\QuickAttributes\Tests\Util\TestExportUtil;
use Donquixote\QuickAttributes\Tests\Util\TestUtil;
use Donquixote\QuickAttributes\Util\ArgumentsUtil;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 *   See https://github.com/sebastianbergmann/phpunit/pull/4795
 */
class RewriteArgumentsTest extends TestCase {

  /**
   * @param string $name
   *
   * @dataProvider providerTestAttrCommentParser()
   */
  public function testRewriteArgs(string $name): void {
    $file = $this->getYmlDir() . '/' . $name . '.yml';
    /** @var array{parameters: list<string>, calls: array} $data */
    $data = Yaml::parseFile($file);
    $this->processData($data);
    $this->processPhp8($data);
    self::normalizeArrayKeys(
      $data,
      ['parameters', 'php', 'exception', 'error.php8', 'calls']);
    /** @psalm-suppress PossiblyUndefinedStringArrayOffset, MixedAssignment */
    foreach ($data['calls'] as &$call) {
      /** @var array $call */
      self::normalizeArrayKeys(
        $call,
        ['arguments', 'rewritten', 'rewritten.php8', 'exception', 'error.php8', 'mismatch']);
    }
    TestUtil::assertFileContentsYml($file, $data);
  }

  /**
   * @param array $data
   */
  private function processData(array &$data): void {

    try {
      $parameters = self::buildParams($data);
      unset($data['exception']);
    }
    catch (\Throwable $e) {
      $data['exception'] = TestExportUtil::exportException($e);
      return;
    }

    /**
     * @psalm-suppress PossiblyUndefinedStringArrayOffset
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArrayAssignment
     * @psalm-suppress MixedArgument
     * @psalm-suppress MixedArrayAccess
     */
    foreach ($data['calls'] as &$call) {
      try {
        $call['rewritten'] = ArgumentsUtil::mapNamedArgs(
          $parameters,
          $call['arguments']);
        unset($call['exception']);
      }
      catch (\Throwable $e) {
        $call['exception'] = TestExportUtil::exportException($e);
        unset($call['rewritten']);
      }
    }
  }

  private function processPhp8(array &$data): void {

    if (PHP_VERSION_ID <= 80000) {
      self::assertTrue(TRUE);
      return;
    }

    try {
      $f = self::createClosure($data);
      unset($data['error.php8']);
      self::assertArrayNotHasKey('exception', $data);
    }
    catch (\Throwable $e) {
      $data['error.php8'] = TestExportUtil::exportException($e);
      self::assertArrayHasKey('exception', $data);
      return;
    }

    /**
     * @psalm-suppress PossiblyUndefinedStringArrayOffset
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArrayAssignment
     * @psalm-suppress MixedArrayAccess
     */
    foreach ($data['calls'] as &$call) {
      unset($call['error.php8']);
      unset($call['mismatch']);
      unset($call['rewritten.php8']);
      try {
        /** @psalm-suppress MixedAssignment */
        /** @var array $rewritten */
        $rewritten = $f(...$call['arguments']);
        if ($rewritten !== ($call['rewritten'] ?? NULL)) {
          $call['rewritten.php8'] = $rewritten;
        }
      }
      catch (\Throwable $e) {
        $call['error.php8'] = TestExportUtil::exportException($e);
        if (isset($call['rewritten'])) {
          $call['mismatch'] = TRUE;
        }
      }
    }
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
    return dirname(__DIR__) . '/fixtures/named-args-rewrite';
  }

  /**
   * @param array $data
   *
   * @return \ReflectionParameter[]
   *
   * @throws \ReflectionException
   */
  private static function buildParams(array &$data): array {
    $f = self::createClosure($data);
    $rf = new \ReflectionFunction($f);
    return $rf->getParameters();
  }

  /**
   * @param array $data
   *
   * @return \Closure
   *
   * @psalm-suppress MixedReturnStatement, MixedInferredReturnType
   */
  private static function createClosure(array &$data): \Closure {
    /** @psalm-suppress MixedArgument */
    $php = 'return static function('
      . implode(', ', $data['parameters'] ?? [])
      . ') {'
      . "\n  return func_get_args();"
      . "\n};";
    $data['php'] = $php;
    return eval($php);
  }

  /**
   * @param array $data
   * @param string[] $keys
   */
  private static function normalizeArrayKeys(array &$data, array $keys): void {
    $normalized = [];
    foreach ($keys as $key) {
      if (isset($data[$key])) {
        /** @psalm-suppress MixedAssignment */
        $normalized[$key] = $data[$key];
      }
    }
    $normalized += $data;
    $data = $normalized;
  }

}
