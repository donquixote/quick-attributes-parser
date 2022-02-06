<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\Loader\SnippetReader;
use Donquixote\QuickAttributes\SymbolInfo\ClassLike\ClassInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\FunctionLike\FunctionInfoInterface;
use Donquixote\QuickAttributes\Tests\Util\TestExportUtil;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 *   See https://github.com/sebastianbergmann/phpunit/pull/4795
 *
 * @psalm-type _AttrCommentsYaml=array{
 *   comment: string,
 *   namespace?: string,
 *   imports?: array<string, string>,
 *   class?: string,
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
    $php = '<?php';
    $php .= "\n" . $this->buildPhpFileHead($data);
    $line = \substr_count($php, "\n");
    if (isset($data['class'])) {
      if (false !== $nspos = \strrpos($data['class'], '\\')) {
        // Class should only be the shortname in these fixtures.
        // The rest should be in the namespace key.
        $data['class'] = \substr($data['class'], $nspos + 1);
      }
      $php .= $data['comment']
        . "\nclass $data[class] {}"
        . "\n";
    }
    else {
      $php .= $data['comment']
        . "\nfunction f() {}"
        . "\n";
    }

    try {
      $info = SnippetReader::create()->loadPhpSnippet($php);
      /** @var ClassInfoInterface|FunctionInfoInterface $element */
      $element = $info->readElements()->current();
      $attributes = $element->getAttributes();
    }
    catch (ParserException $e) {
      $e->setSourceFile(null, null, -$line);
      unset($data['attributes']);
      $data['exception'] = TestExportUtil::exportException($e);
      return;
    }
    catch (\InvalidArgumentException $e) {
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

  /**
   * @param _AttrCommentsYaml $data
   *
   * @return string
   */
  protected function buildPhpFileHead(array $data): string {
    $php = '';
    if (isset($data['namespace'])) {
      $php .= "namespace $data[namespace];\n";
    }
    foreach ($data['imports'] ?? [] as $alias => $qcn) {
      // Optimize for the more common case where the alias has no space.
      if (false !== $spacepos = \strpos($alias, ' ')) {
        $type = \substr($alias, 0, $spacepos);
        $alias = \substr($alias, $spacepos + 1);
        $php .= "use $type $qcn as $alias;\n";
      }
      else {
        $php .= "use $qcn as $alias;\n";
      }
    }
    return $php;
  }

}
