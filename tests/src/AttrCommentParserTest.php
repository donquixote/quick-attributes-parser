<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

use Donquixote\QuickAttributes\AttributeCommentParser\AttributeCommentParser;
use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\Tests\Util\TestExportUtil;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 *   See https://github.com/sebastianbergmann/phpunit/pull/4795
 *
 * @psalm-type _AttrCommentsYaml=array{
 *   comment: string,
 *   namespace?: string,
 *   imports?: array<string, string>,
 *   class?: class-string,
 *   attributes?: list<array{
 *     name: class-string,
 *     arguments?: array,
 *     exception?: array,
 *   }>,
 *   'attributes.php8'?: list<array{
 *     name: class-string,
 *     arguments?: array,
 *     exception?: array,
 *   }>,
 *   'attributes.native'?: list<array{
 *     name: class-string,
 *     arguments?: array,
 *     exception?: array,
 *   }>,
 *   exception?: array,
 *   'exception.php8'?: array,
 *   'exception.native'?: array,
 *   mismatch?: true,
 * }
 *
 * @template-extends YmlTestBase<_AttrCommentsYaml>
 */
class AttrCommentParserTest extends YmlTestBase {

  protected function getKnownKeys(): array {
    return [
      'comment',
      'namespace',
      'imports',
      'class',
      'fatal',
      'attributes',
      'attributes.php8',
      'attributes.native',
      'exception',
      'exception.php8',
      'exception.native',
      'mismatch',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function processData(array &$data, string $name): void {

    $parser = new AttributeCommentParser();
    $parser = $parser->withContext(
      $data['namespace'] ?? null,
      $data['imports'] ?? [],
      $data['class'] ?? null);

    try {
      $attributes = $parser->parse($data['comment'] . "\n");
    }
    catch (ParserException|\InvalidArgumentException $e) {
      unset($data['attributes']);
      $data['exception'] = TestExportUtil::exportException($e);
      return;
    }

    unset($data['exception']);
    $data['attributes'] = TestExportUtil::exportRawAttributes($attributes);
  }

  protected function getYmlSubdir(): string {
    return 'attr-comments';
  }

}
