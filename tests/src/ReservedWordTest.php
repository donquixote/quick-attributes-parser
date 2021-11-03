<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

use Donquixote\QuickAttributes\Tests\Util\TestUtil;
use Donquixote\QuickAttributes\Util\ReservedWordUtil;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 *   See https://github.com/sebastianbergmann/phpunit/pull/4795
 */
class ReservedWordTest extends TestCase {

  public function test(): void {
    $file = dirname(__DIR__) . '/fixtures/misc/valid-member-name.yml';
    /**
     * @var array<string, bool|string> $data
     */
    $data = Yaml::parseFile($file);
    foreach ($data as $word => $_) {
      $data[$word] = ReservedWordUtil::validMemberName($word);
    }
    ksort($data);
    TestUtil::assertFileContentsYml($file, $data);
  }

}
