<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests\Util;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;
use Symfony\Component\Yaml\Yaml;

class TestUtil {

  /**
   * Checks an env flag that determines if fixture files should be updated.
   *
   * @return bool
   *   If TRUE, fixture files will be overwritten.
   */
  public static function updateTestsEnabled(): bool {
    return (bool) getenv('UPDATE_TESTS');
  }

  /**
   * @param string $file
   * @param mixed $data
   * @param bool $writeIfEnabled
   * @param int $inline
   */
  public static function assertFileContentsYml(string $file, $data, bool $writeIfEnabled = TRUE, int $inline = 99): void {
    self::assertFileContents(
      $file,
      Yaml::dump(
        $data,
        $inline,
        4,
        Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK | Yaml::DUMP_OBJECT_AS_MAP | Yaml::DUMP_OBJECT),
      $writeIfEnabled);
  }

  /**
   * @param string $file
   *   File with expected content.
   * @param string $content_actual
   *   Actual content.
   * @param bool $writeIfEnabled
   */
  public static function assertFileContents(string $file, string $content_actual, bool $writeIfEnabled = TRUE): void {
    try {
      if (!is_file($file)) {
        Assert::fail("File '$file' is missing.");
      }
      $content_expected = file_get_contents($file);
      Assert::assertSame($content_expected, $content_actual);
    }
    catch (AssertionFailedError $e) {
      if ($writeIfEnabled && self::updateTestsEnabled()) {
        file_put_contents($file, $content_actual);
      }
      throw $e;
    }
  }

  /**
   * @param string $file
   * @param string $message
   */
  public static function foundOrphanFile(string $file, string $message): void {
    if (self::updateTestsEnabled()) {
      # unlink($file);
    }
    Assert::fail("$message: $file");
  }

}
