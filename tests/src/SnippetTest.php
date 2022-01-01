<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\FileTokens\FileTokens_Common;
use Donquixote\QuickAttributes\Parser\FileParser;
use Donquixote\QuickAttributes\SymbolVisitor\SymbolVisitor_CollectInfo;
use Donquixote\QuickAttributes\Tests\Util\TestArrayUtil;
use Donquixote\QuickAttributes\Tests\Util\TestExportUtil;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 *   See https://github.com/sebastianbergmann/phpunit/pull/4795
 *
 * @psalm-type _SnippetYamlContent=array{
 *   php: string,
 *   importss: array<string, array<string, string>>,
 *   attributess: array<string, array{
 *     imports?: array<string, string>,
 *     'attr-comments'?: list<string>,
 *   }>,
 *   exception?: array,
 *   tokenizer_split?: bool,
 * }
 *
 * @template-extends YmlTestBase<_SnippetYamlContent>
 */
class SnippetTest extends YmlTestBase {

  /**
   * {@inheritdoc}
   */
  protected function processData(array &$data, string $name): void {
    if (\PHP_VERSION_ID >= 80000) {
      self::assertTrue(true);
      return;
    }
    $fileTokens = new FileTokens_Common($data['php']);
    $parser = FileParser::create();
    $visitor = new SymbolVisitor_CollectInfo();
    try {
      unset($data['attributess']);
      unset($data['importss']);
      // Parse all.
      /** @noinspection PhpUnusedLocalVariableInspection */
      foreach ($parser->parseFileTokens($fileTokens, $visitor) as $_) {}
      unset($data['exception']);
    }
    catch (ParserException $e) {
      $data['exception'] = TestExportUtil::exportException($e);
    }
    $importss = $visitor->getImportss();
    if ($importss) {
      $data['importss'] = $importss;
    }
    foreach ($visitor->getAttributess() as $key => $attributes) {
      $data['attributess'][$key] = TestExportUtil::exportRawAttributes($attributes);
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
