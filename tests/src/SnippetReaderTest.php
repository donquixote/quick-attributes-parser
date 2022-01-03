<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\FileTokens\FileTokens_Common;
use Donquixote\QuickAttributes\Registry\FileTokensReader;
use Donquixote\QuickAttributes\SymbolInfo\ClassInfo;
use Donquixote\QuickAttributes\SymbolInfo\FunctionInfo;
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
    $reader = FileTokensReader::create();
    $data7 = $data;
    $attributess = [];
    try {
      unset($data['attributess']);
      unset($data['importss']);
      $secondaryIterator = $reader->read($fileTokens);
      // Parse all.
      foreach ($reader->read($fileTokens) as $toplevel => $element) {
        self::assertTrue($secondaryIterator->valid());
        self::assertSame($toplevel, $secondaryIterator->key());
        $secondaryElement = $secondaryIterator->current();
        $data['importss'][$toplevel] = $element->getImports();
        $attributess[$toplevel] = $element->getAttributes();
        /** @psalm-suppress RedundantCondition */
        if ($element instanceof ClassInfo) {
          self::assertTrue($secondaryElement instanceof ClassInfo);
          foreach ($element->readMemberTypes() as $member => $type) {
            self::assertMatchesRegularExpression('@^\w+$@', $member);
            if ($type === 'function') {
              $method = $element->methodGetInfo($member);
              self::assertNotNull($method);
              $attributess[$toplevel . '::' . $member . '()'] = $method->getAttributes();
              foreach ($method->readParameters() as $param => $attributes) {
                $attributess[$toplevel . '::' . $member . '($' . $param . ')'] = $attributes;
              }
              $m2 = $secondaryElement->methodGetInfo($member);
              self::assertNotNull($m2);
            }
            elseif ($type === '$') {
              $attributess[$toplevel . '::$' . $member] = $element->propertyGetAttributes($member);
            }
            elseif ($type === 'const') {
              $attributess[$toplevel . '::' . $member] = $element->constGetAttributes($member);
            }
            else {
              self::fail('Unexpected type.');
            }
          }
        }
        elseif ($element instanceof FunctionInfo) {
          self::assertTrue($secondaryElement instanceof FunctionInfo);
          $function = \substr($toplevel, 0, -2);
          foreach ($element->readParameters() as $param => $attributes) {
            $attributess[$function . '($' . $param . ')'] = $attributes;
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
      $data['attributess'][$key] = ($attributes !== null)
        ? TestExportUtil::exportRawAttributes($attributes)
        : null;
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
