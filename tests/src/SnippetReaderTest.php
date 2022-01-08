<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\SnippetReader\SnippetReader;
use Donquixote\QuickAttributes\SymbolInfo\ClassLike\ClassInfo;
use Donquixote\QuickAttributes\SymbolInfo\ClassMember\MethodInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\FunctionLike\FunctionInfoInterface;
use Donquixote\QuickAttributes\Tests\Util\TestExportUtil;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 *   See https://github.com/sebastianbergmann/phpunit/pull/4795
 */
class SnippetReaderTest extends SnippetTest {

  /**
   * {@inheritdoc}
   */
  protected function processData(array &$data, string $name): void {
    $loader = SnippetReader::create();
    $attributess = [];
    try {
      unset($data['attributess']);
      unset($data['importss']);
      $secondaryIterator = $loader->loadPhpSnippet($data['php'])->readElements();
      // Parse all.
      foreach ($loader->loadPhpSnippet($data['php'])->readElements() as $i => $element) {
        self::assertTrue($secondaryIterator->valid());
        self::assertSame($i, $secondaryIterator->key());
        $secondaryElement = $secondaryIterator->current();
        $data['importss'][$element->getId()] = $element->getImports();
        $attributess[$element->getId()] = $element->getAttributes();
        if ($element instanceof ClassInfo) {
          if (!$secondaryElement instanceof ClassInfo) {
            self::fail();
          }
          foreach ($element->readMembers() as $member) {
            $attributess[$member->getId()] = $member->getAttributes();
            if ($member instanceof MethodInfoInterface) {
              foreach ($member->readParameters() as $param) {
                $attributess[$param->getId()] = $param->getAttributes();
              }
              $m2 = $secondaryElement->findMethod($member->getName());
              self::assertNotNull($m2);
            }
          }
        }
        elseif ($element instanceof FunctionInfoInterface) {
          self::assertTrue($secondaryElement instanceof FunctionInfoInterface);
          $function = $element->getName();
          foreach ($element->readParameters() as $param) {
            $attributess[$function . '($' . $param->getName() . ')'] = $param->getAttributes();
          }
        }
        else {
          self::fail('Element has unexpected type.');
        }
        $secondaryIterator->next();
      }
      unset($data['exception']);
    }
    catch (ParserException $e) {
      $data['exception'] = TestExportUtil::exportException($e);
    }
    foreach ($attributess as $key => $attributes) {
      $data['attributess'][$key] = TestExportUtil::exportRawAttributes($attributes);
    }
  }

  protected function getYmlSubdir(): string {
    return 'snippet';
  }

  protected function writeEnabled(): bool {
    return false;
  }

}
