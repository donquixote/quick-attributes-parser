<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\Loader\SnippetReader;
use Donquixote\QuickAttributes\SymbolInfo\ClassLike\ClassInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\ClassMember\ClassConstInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\ClassMember\MethodInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\ClassMember\PropertyInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\FunctionLike\FunctionInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\Parameter\ParamInfoInterface;
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
        if ($element instanceof ClassInfoInterface) {
          if (!$secondaryElement instanceof ClassInfoInterface) {
            self::fail();
          }
          $prefix = $element->getName() . '::';
          /**
           * @var PropertyInfoInterface|ClassConstInfoInterface|MethodInfoInterface $member
           * @psalm-ignore-var
           */
          foreach ($element->readMembers() as $member) {
            $attributess[$prefix . $member->getMemberId()] = $member->getAttributes();
            if ($member instanceof MethodInfoInterface) {
              $methodPrefix = $prefix . $member->getName() . '($';
              /**
               * @var ParamInfoInterface $param
               * @psalm-ignore-var
               */
              foreach ($member->readParameters() as $param) {
                $attributess[$methodPrefix . $param->getName() . ')'] = $param->getAttributes();
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
