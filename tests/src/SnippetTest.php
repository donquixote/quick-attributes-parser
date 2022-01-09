<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\FileTokens\FileTokens_Common;
use Donquixote\QuickAttributes\Parser\FileParser;
use Donquixote\QuickAttributes\SymbolVisitor\SymbolVisitor_CollectInfo;
use Donquixote\QuickAttributes\Tests\Util\TestExportUtil;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 *   See https://github.com/sebastianbergmann/phpunit/pull/4795
 *
 * @psalm-type _SnippetYamlContent=array{
 *   php: string,
 *   php_version_ids?: list<int>,
 *   importss: array<string, array<string, string>>,
 *   'importss.php8'?: array<string, array<string, string>>,
 *   attributess: array<string, array{
 *     imports?: array<string, string>,
 *     'attr-comments'?: list<string>,
 *   }>,
 *   'attributess.php8'?: array<string, array{
 *     imports?: array<string, string>,
 *     'attr-comments'?: list<string>,
 *   }>,
 *   tokenizer_split?: bool,
 *   exception?: array,
 *   'exception.php8'?: array|null,
 *   'exception.php81'?: array|null,
 * }
 *
 * @template-extends YmlTestBase<_SnippetYamlContent>
 */
class SnippetTest extends YmlTestBase {

  /**
   * {@inheritdoc}
   */
  protected function processData(array &$data, string $name): void {
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
  }

  /**
   * @return list<string>
   */
  protected function getKnownKeys(): array {
    return [
      'php',
      'php_version_ids',
      'importss',
      'importss.php8',
      'attributess',
      'attributess.php8',
      'tokenizer_split',
      'exception',
      'exception.php8',
      'exception.php801',
    ];
  }

  protected function getYmlSubdir(): string {
    return 'snippet';
  }

}
