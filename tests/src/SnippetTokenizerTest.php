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
    $classes = \array_values(\preg_grep(
      '@^[\w\\\\]+$@',
      \array_keys($data['importss'] ?? [])));
    $class = $classes[0] ?? null;
    $hasClass = ($class !== null);
    unset($data['tokenizer_split']);
    if ($head !== null) {
      // The file was split.
      self::assertSame('#', \array_pop($head));
      self::assertSame(\array_slice($tokensExpected, 0, \count($head)), $head);
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
    if ($hasClass) {
      $parts = \explode('\\', $class);
      $shortname = $parts[\count($parts) - 1];
      $fileTokensNamed = new FileTokens_Common($data['php'], $shortname);
      try {
        $headNamed = $fileTokensNamed->getClassFileHead();
        $allNamed = $fileTokensNamed->getAll();
        self::assertSame('#', \array_pop($allNamed));
        self::assertSame($all, $allNamed);
        if ($headNamed !== null) {
          self::assertSame('#', \array_pop($headNamed));
          self::assertSame($head, $headNamed);
        }
      }
      catch (ParserException $e) {
        self::fail(\get_class($e) . ': ' . $e->getMessage());
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

  protected function writeEnabled(): bool {
    return false;
  }

}
