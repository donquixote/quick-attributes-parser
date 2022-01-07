<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\FileTokens\FileTokens_Common;
use Donquixote\QuickAttributes\Registry\FileInfoLoader;
use Donquixote\QuickAttributes\SymbolInfo\ClassLike\ClassInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\ClassLike\FunctionInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\ClassMember\MethodInfo;
use Donquixote\QuickAttributes\Tests\Util\TestArrayUtil;
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
    $fileTokens = new FileTokens_Common($data['php']);
    $loader = FileInfoLoader::create();
    $data7 = $data;
    $attributess = [];
    try {
      unset($data['attributess']);
      unset($data['importss']);
      $secondaryIterator = $loader->loadFileTokens($fileTokens)->readElements();
      // Parse all.
      /**
       * @var int $i
       * @var ClassInfoInterface|FunctionInfoInterface $element
       * @psalm-ignore-var
       */
      foreach ($loader->loadFileTokens($fileTokens)->readElements() as $i => $element) {
        self::assertTrue($secondaryIterator->valid());
        self::assertSame($i, $secondaryIterator->key());
        $secondaryElement = $secondaryIterator->current();
        $data['importss'][$element->getId()] = $element->getImports();
        $attributess[$element->getId()] = $element->getAttributes();
        /** @psalm-suppress RedundantCondition */
        if ($element instanceof ClassInfoInterface) {
          if (!$secondaryElement instanceof ClassInfoInterface) {
            self::fail();
          }
          $prefix = $element->getName() . '::';
          /**
           * @var \Donquixote\QuickAttributes\SymbolInfo\ClassMember\PropertyInfoInterface|\Donquixote\QuickAttributes\SymbolInfo\ClassMember\ClassConstInfoInterface|\Donquixote\QuickAttributes\SymbolInfo\ClassMember\MethodInfoInterface $member
           * @psalm-ignore-var
           */
          foreach ($element->readMembers() as $member) {
            $attributess[$prefix . $member->getMemberId()] = $member->getAttributes();
            if ($member instanceof MethodInfo) {
              $methodPrefix = $prefix . $member->getName() . '($';
              /**
               * @var \Donquixote\QuickAttributes\SymbolInfo\Parameter\ParamInfoInterface $param
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
