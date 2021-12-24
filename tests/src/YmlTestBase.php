<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

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
    $this->processData($data, $name);
    $file = $this->getFile($name);
    TestUtil::assertFileContentsYml($file, $data);
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
