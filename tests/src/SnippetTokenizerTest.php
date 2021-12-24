<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\Exception\TokenizerException;
use Donquixote\QuickAttributes\FileTokens\FileTokens_Common;
use Donquixote\QuickAttributes\Tests\Util\TestArrayUtil;
use Donquixote\QuickAttributes\Tests\Util\TestExportUtil;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 *   See https://github.com/sebastianbergmann/phpunit/pull/4795
 */
class SnippetTokenizerTest extends SnippetTest {

  /**
   * {@inheritdoc}
   */
  protected function processData(array &$data, string $name): void {
    if (\PHP_VERSION_ID >= 80000) {
      self::assertTrue(true);
      return;
    }
    $tokensExpected = @\token_get_all($data['php']);
    $fileTokens = new FileTokens_Common($data['php']);
    try {
      $head = $fileTokens->getClassFileHead();
      $all = $fileTokens->getAll();
    }
    catch (ParserException $e) {
      self::assertSame(TestExportUtil::exportException($e), $data['exception'] ?? null);
      return;
    }
    // Verify that no tokenizer exception is in $data.
    self::assertNotSame(TokenizerException::class, $data['exception']['class'] ?? null);
    self::assertSame('#', \array_pop($all));
    self::assertSame($tokensExpected, $all);
    $hasClass = (bool) \preg_grep(
      '@^[\w\\\\]+$@',
      \array_keys($data['importss'] ?? []));
    unset($data['tokenizer_split']);
    if ($head !== NULL) {
      // The file was split.
      self::assertSame('#', \array_pop($head));
      self::assertSame(\array_slice($tokensExpected, 0, count($head)), $head);
      self::assertSame('{', \end($head));
      if (!$hasClass && !isset($data['exception'])) {
        $data['tokenizer_split'] = true;
      }
    }
    else {
      // The file was not split.
      if ($hasClass) {
        $data['tokenizer_split'] = false;
      }
    }
    TestArrayUtil::normalizeKeys($data, [
      'php',
      'importss',
      'attributess',
      'tokenizer_split',
    ]);
  }

  protected function getYmlSubdir(): string {
    return 'snippet';
  }

}
