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
    $file = $this->getYmlDir() . '/' . $name . '.yml';
    /** @var T $data */
    $data = Yaml::parseFile($file);
    $this->processData($data);
    TestUtil::assertFileContentsYml($file, $data);
  }

  /**
   * @param T $data
   */
  abstract protected function processData(array &$data): void;

  /**
   * @return iterable<int, array{string}>
   */
  public function provider(): iterable {
    $ymlDir = $this->getYmlDir();
    foreach (scandir($ymlDir) as $candidate) {
      if (preg_match('@^(\w+(?:[\.\-]\w+)*)\.yml$@', $candidate, $m)) {
        yield [$m[1]];
      }
    }
  }

  protected function getYmlDir(): string {
    return dirname(__DIR__) . '/fixtures/' . $this->getYmlSubdir();
  }

  abstract protected function getYmlSubdir(): string;

}