<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

use Donquixote\QuickAttributes\Tests\Util\TestArrayUtil;
use Donquixote\QuickAttributes\Tests\Util\TestUtil;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * @template T as array
 */
abstract class YmlTestBase extends TestCase {

  /**
   * @dataProvider provider()
   */
  public function test(string $name): void {
    $data = $this->loadData($name);
    $this->processDataByVersion($data, $name);
    $file = $this->getFile($name);
    TestUtil::assertFileContentsYml($file, $data, $this->writeEnabled());
  }

  /**
   * @param T $data
   * @param string $name
   *
   * @psalm-suppress ReferenceConstraintViolation
   */
  protected function processDataByVersion(array &$data, string $name): void {
    $keys = $this->getKnownKeys();
    $vdkeys = [];
    foreach ($keys as $key) {
      if (\preg_match('/^(\w+)\.php(\d{5})0*$/', $key . '0000', $m)) {
        if (\in_array($m[1], $keys)) {
          $vdkeys[$m[1]][$m[2]] = $key;
        }
      }
    }
    $orig = $data;
    foreach ($vdkeys as $basekey => $versions) {
      foreach ($versions as $versionId => $key) {
        if (\PHP_VERSION_ID < $versionId) {
          break;
        }
        if (isset($data[$key])) {
          /** @psalm-suppress MixedAssignment */
          $data[$basekey] = $data[$key];
        }
        elseif (\array_key_exists($key, $data)) {
          unset($data[$basekey]);
        }
      }
    }
    /** @psalm-suppress PossiblyInvalidArgument */
    $this->processData($data, $name);
    foreach ($vdkeys as $basekey => $versions) {
      $prevkey = $basekey;
      $lastkey = $basekey;
      foreach ($versions as $versionId => $key) {
        if (\PHP_VERSION_ID < $versionId) {
          break;
        }
        if (\array_key_exists($lastkey, $data)) {
          $prevkey = $lastkey;
        }
        $lastkey = $key;
      }
      if ($lastkey !== $basekey) {
        \assert($prevkey !== $lastkey);
        if (($data[$basekey] ?? null) === ($orig[$prevkey] ?? null)) {
          unset($data[$lastkey]);
        }
        else {
          /** @psalm-suppress MixedAssignment */
          $data[$lastkey] = $data[$basekey] ?? null;
        }
        if (isset($orig[$basekey])) {
          /** @psalm-suppress MixedAssignment */
          $data[$basekey] = $orig[$basekey];
        }
        else {
          unset($data[$basekey]);
        }
      }
    }
    TestArrayUtil::normalizeKeys($data, $keys);
  }

  /**
   * @param string $name
   *
   * @return string
   */
  protected function getFile(string $name): string {
    return $this->getYmlDir() . '/' . $name . '.yml';
  }

  /**
   * @param string $name
   *
   * @return T
   */
  protected function loadData(string $name): array {
    $file = $this->getFile($name);
    /** @var T */
    return Yaml::parseFile($file);
  }

  /**
   * @param T $data
   * @param string $name
   */
  abstract protected function processData(array &$data, string $name): void;

  /**
   * @return list<string>
   */
  abstract protected function getKnownKeys(): array;

  protected function writeEnabled(): bool {
    return true;
  }

  /**
   * @return iterable<string, array{string}>
   */
  public function provider(): iterable {
    $ymlDir = $this->getYmlDir();
    foreach (\scandir($ymlDir) as $candidate) {
      if (\preg_match('@^(\w+(?:[\.\-]\w+)*)\.yml$@', $candidate, $m)) {
        yield $m[1] => [$m[1]];
      }
    }
  }

  protected function getYmlDir(): string {
    return \dirname(__DIR__) . '/fixtures/' . $this->getYmlSubdir();
  }

  abstract protected function getYmlSubdir(): string;

}
