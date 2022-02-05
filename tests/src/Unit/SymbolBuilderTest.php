<?php

/**
 * @noinspection PhpUnhandledExceptionInspection
 */

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests\Unit;

use Donquixote\QuickAttributes\SymbolInfo\ClassLike\ClassInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\ClassMember\MethodInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\File\FileInfo;
use PHPUnit\Framework\TestCase;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 *   See https://github.com/sebastianbergmann/phpunit/pull/4795
 */
class SymbolBuilderTest extends TestCase {

  public function testEmptyFileInfo(): void {
    $fileInfo = $this->createEmptyFileInfo();

    $classBuilder = $fileInfo->addClass(self::class, []);
    $classBodyBuilder = $classBuilder->buildClassBody();
    $classInfo = $fileInfo->readClasses()->current();
    self::assertInstanceOf(ClassInfoInterface::class, $classInfo);
    self::assertSame(self::class, $classInfo->getName());
    self::assertSame([], $classInfo->getAttributes());

    $parametersBuilder = $classBodyBuilder->addMethod('f0')->buildParameters();
    $methodInfo = $classInfo->readMethods()->current();
    self::assertInstanceOf(MethodInfoInterface::class, $methodInfo);

    $parametersBuilder->addParameter('a');
    $parametersBuilder->addParameter('b');
    $parametersBuilder->markAsComplete();
    $paramInfo = $methodInfo->findParameter('b');
    self::assertNotNull($paramInfo);
    self::assertSame('b', $paramInfo->getName());

    $parametersBuilder = $classBodyBuilder->addMethod('f1')->buildParameters();
    $parametersBuilder->addParameter('x');
    $parametersBuilder->markAsComplete();
    self::assertNotNull($methodInfo = $classInfo->findMethod('f1'));
    self::assertNotNull($methodInfo->findParameter('x'));

    $classBodyBuilder->addMethod('f2');
    self::assertNotNull($classInfo->findMethod('f2'));

    $classBodyBuilder->addMethod('f3');
    self::assertNotNull($classInfo->findMethod('f3'));

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Parser did not close this class.');
    self::assertNull($classInfo->findMethod('fNonExistent'));
  }

  protected function createEmptyFileInfo(): FileInfo {
    return new class () extends FileInfo {
      public function __construct() {
        parent::__construct(static function (): \Iterator {
          yield true;
        });
      }
    };
  }

}
