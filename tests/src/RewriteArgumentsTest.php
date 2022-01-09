<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

use Donquixote\QuickAttributes\Tests\Util\TestArrayUtil;
use Donquixote\QuickAttributes\Tests\Util\TestExportUtil;
use Donquixote\QuickAttributes\Util\ArgumentsUtil;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 *   See https://github.com/sebastianbergmann/phpunit/pull/4795
 *
 * @psalm-type _RewriteArgsCall=array{
 *   arguments: array,
 *   rewritten?: list<mixed>,
 *   'rewritten.php8'?: list<mixed>,
 *   exception?: array,
 *   'error.php8'?: array,
 *   mismatch?: true,
 * }
 *
 * @psalm-type _RewriteArgsYamlContent=array{
 *   parameters: list<string>,
 *   php?: string,
 *   exception?: array,
 *   'error.php8'?: array,
 *   calls: array<string, _RewriteArgsCall>,
 * }
 *
 * @template-extends YmlTestBase<_RewriteArgsYamlContent>
 */
class RewriteArgumentsTest extends YmlTestBase {

  protected function getKnownKeys(): array {
    return [
      'parameters',
      'php',
      'exception',
      'error.php8',
      'calls',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function processData(array &$data, string $name): void {
    $this->processCommon($data);
    $this->processPhp8($data);
    foreach ($data['calls'] as &$call) {
      /** @var array $call */
      TestArrayUtil::normalizeKeys(
        $call,
        ['arguments', 'rewritten', 'rewritten.php8', 'exception', 'error.php8', 'mismatch']);
    }
  }

  /**
   * @param _RewriteArgsYamlContent $data
   */
  private function processCommon(array &$data): void {

    try {
      $parameters = self::buildParams($data);
      unset($data['exception']);
    }
    catch (\Throwable $e) {
      $data['exception'] = TestExportUtil::exportException($e);
      return;
    }

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

  /**
   * @param _RewriteArgsYamlContent $data
   */
  private function processPhp8(array &$data): void {

    if (\PHP_VERSION_ID <= 80000) {
      self::assertTrue(true);
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

    foreach ($data['calls'] as &$call) {
      unset($call['error.php8']);
      unset($call['mismatch']);
      unset($call['rewritten.php8']);
      try {
        /** @psalm-suppress MixedAssignment */
        /** @var array $rewritten */
        $rewritten = $f(...$call['arguments']);
        if ($rewritten !== ($call['rewritten'] ?? null)) {
          $call['rewritten.php8'] = $rewritten;
        }
      }
      catch (\Throwable $e) {
        $call['error.php8'] = TestExportUtil::exportException($e);
        if (isset($call['rewritten'])) {
          $call['mismatch'] = true;
        }
      }
    }
  }

  protected function getYmlSubdir(): string {
    return 'named-args-rewrite';
  }

  /**
   * @param _RewriteArgsYamlContent $data
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
   * @param _RewriteArgsYamlContent $data
   *
   * @return \Closure
   *
   * @psalm-suppress MixedReturnStatement, MixedInferredReturnType
   */
  private static function createClosure(array &$data): \Closure {
    $php = 'return static function('
      . \implode(', ', $data['parameters'] ?? [])
      . ') {'
      . "\n  return func_get_args();"
      . "\n};";
    $data['php'] = $php;
    return eval($php);
  }

}
