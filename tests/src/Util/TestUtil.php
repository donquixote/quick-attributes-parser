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
   */
  public static function assertFileContentsYml(string $file, $data): void {
    self::assertFileContents(
      $file,
      Yaml::dump($data));
  }

  /**
   * @param string $file
   *   File with expected content.
   * @param string $content_actual
   *   Actual content.
   */
  public static function assertFileContents(string $file, string $content_actual): void {
    try {
      if (!is_file($file)) {
        Assert::fail("File '$file' is missing.");
      }
      $content_expected = file_get_contents($file);
      Assert::assertSame($content_expected, $content_actual);
    }
    catch (AssertionFailedError $e) {
      if (self::updateTestsEnabled()) {
        file_put_contents($file, $content_actual);
      }
      throw $e;
    }
  }

  /**
   * @param string $file
   * @param string $message
   */
  public static function foundOrphanFile(string $file, string $message) {
    if (self::updateTestsEnabled()) {
      # unlink($file);
    }
    Assert::fail("$message: $file");
  }

}
