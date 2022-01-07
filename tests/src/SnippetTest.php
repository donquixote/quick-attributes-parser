<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\FileTokens\FileTokens_Common;
use Donquixote\QuickAttributes\Parser\FileParser;
use Donquixote\QuickAttributes\SymbolVisitor\File\SymbolVisitor_CollectImportsAndAttributes;
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
 *   tokenizer_split?: bool,
 *   exception?: array,
 *   'exception.php8'?: array,
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
    $data7 = $data;
    $importss = [];
    $attributess = [];
    try {
      unset($data['attributess']);
      unset($data['importss']);
      $visitor = new SymbolVisitor_CollectImportsAndAttributes(
        $importss,
        $attributess);
      // Parse all.
      /** @noinspection PhpUnusedLocalVariableInspection */
      foreach ($parser->parseFileTokens($fileTokens, $visitor) as $_) {}
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
    if (\PHP_VERSION_ID >= 80000) {
      // PHP 8.
      // Currently, exceptions are the only point where the fixtures differ.
      if (($data['exception'] ?? null) === ($data7['exception'] ?? null)) {
        unset($data['exception.php8']);
      }
      else {
        $data['exception.php8'] = $data['exception'] ?? null;
        if (isset($data7['exception'])) {
          $data['exception'] = $data7['exception'];
        }
        else {
          unset($data['exception']);
        }
      }
    }
    TestArrayUtil::normalizeKeys($data, [
      'php',
      'importss',
      'attributess',
      'tokenizer_split',
      'exception',
      'exception.php8',
    ]);
  }

  protected function getYmlSubdir(): string {
    return 'snippet';
  }

}
