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
    if (!\getenv('UPDATE_TESTS')) {
      return false;
    }
    if (\DIRECTORY_SEPARATOR === '\\') {
      throw new \RuntimeException(
        'Cannot update tests in Windows OS, because file would be written with wrong line endings and directory separator.');
    }
    return true;
  }

  /**
   * @param string $file
   * @param mixed $data
   * @param bool $writeIfEnabled
   * @param int $inline
   */
  public static function assertFileContentsYml(string $file, $data, bool $writeIfEnabled = true, int $inline = 99): void {
    self::assertFileContents(
      $file,
      Yaml::dump(
        $data,
        $inline,
        2,
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
  public static function assertFileContents(string $file, string $content_actual, bool $writeIfEnabled = true): void {
    try {
      if (!\is_file($file)) {
        Assert::fail("File '$file' is missing.");
      }
      $content_expected = \file_get_contents($file);
      // Detect Windows.
      if (\DIRECTORY_SEPARATOR === '\\') {
        // Deal with Windows directory separators.
        $content_actual = \preg_replace_callback(
          '@\[\.\.\]((?:\\\\\w+)+)@',
          static function (array $match) {
            /** @var array{string} $match */
            return \str_replace('\\', '/', $match[0]);
          },
          $content_actual);
      }
      Assert::assertSame($content_expected, $content_actual);
    }
    catch (AssertionFailedError $e) {
      if ($writeIfEnabled && self::updateTestsEnabled()) {
        \file_put_contents($file, $content_actual);
      }
      throw $e;
    }
  }

}
