<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

use Donquixote\QuickAttributes\Builder\File\FileBuilder_CollectImportsAndAttributes;
use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\FileTokens\FileTokens_Common;
use Donquixote\QuickAttributes\Parser\FileTokenParser;
use Donquixote\QuickAttributes\Tests\Util\TestExportUtil;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 *   See https://github.com/sebastianbergmann/phpunit/pull/4795
 *
 * @psalm-type _SnippetYamlContent=array{
 *   php: string,
 *   php_version_ids?: list<int>,
 *   importss: array<string, array<string, string>>,
 *   attributess: array<string, array{
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
    $parser = FileTokenParser::create();
    $importss = [];
    $importssRef = [];
    $attributess = [];
    $attributessRef = [];
    try {
      unset($data['attributess']);
      unset($data['importss']);
      $builder = new FileBuilder_CollectImportsAndAttributes(
        $importssRef,
        $attributessRef);
      // Parse all.
      /** @noinspection PhpUnusedLocalVariableInspection */
      foreach ($parser->parseFileTokens($fileTokens, $builder) as $_) {
        $importss = $importssRef;
        $attributess = $attributessRef;
      }
      unset($data['exception']);
    }
    catch (ParserException $e) {
      $data['exception'] = TestExportUtil::exportException($e);
    }
    if ($importss) {
      $data['importss'] = $importss;
    }
    foreach ($attributess as $key => $attributes) {
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
      'attributess',
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
