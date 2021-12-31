<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

use Donquixote\QuickAttributes\AttributeCommentParser\AttributeCommentParser;
use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\FileTokens\FileTokens_Common;
use Donquixote\QuickAttributes\Parser\FileParser;
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
    $parser = new FileParser();
    $attrParser = new AttributeCommentParser();
    try {
      unset($data['attributess']);
      unset($data['importss']);
      /**
       * @var \Donquixote\QuickAttributes\Value\SymbolHandle $h
       * @psalm-ignore-var
       */
      foreach ($parser->parseFileTokens($fileTokens) as $h => $info) {
        $imports = $info->getImports();
        if ($imports !== null) {
          $data['importss'][(string) $h] = $imports;
        }
        $attrComments = $info->getAttributeComments();
        $exportedAttributes = [];
        if ($attrComments) {
          $imports = $data['importss'][(string) $h->getTopLevel()] ?? [];
          $attrParser = $attrParser->withContext(
            $h->getNamespaceName(),
            $imports,
            null);
          foreach ($attrComments as $comment) {
            foreach ($attrParser->parse($comment) as $attr) {
              $exportedAttributes[] = TestExportUtil::exportRawAttribute($attr);
            }
          }
        }
        $data['attributess'][(string) $h] = $exportedAttributes;
      }
      unset($data['exception']);
    }
    catch (ParserException $e) {
      $data['exception'] = TestExportUtil::exportException($e);
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
