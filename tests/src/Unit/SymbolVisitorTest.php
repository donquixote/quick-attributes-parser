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
class SymbolVisitorTest extends TestCase {

  public function testEmptyFileInfo(): void {
    $fineInfo = $this->createEmptyFileInfo();

    $memberVisitor = $fineInfo->addClass(self::class, [], []);
    $classInfo = $fineInfo->readClasses()->current();
    self::assertInstanceOf(ClassInfoInterface::class, $classInfo);
    self::assertSame($memberVisitor, $classInfo);
    self::assertSame(self::class, $classInfo->getName());
    self::assertSame([], $classInfo->getAttributes());

    $paramVisitor = $memberVisitor->addMethod('f0', []);
    $methodInfo = $classInfo->readMethods()->current();
    self::assertInstanceOf(MethodInfoInterface::class, $methodInfo);
    self::assertSame($paramVisitor, $methodInfo);

    $paramVisitor->addParameter('a', []);
    $paramVisitor->addParameter('b', []);
    $paramVisitor->markAsComplete();
    $paramInfo = $methodInfo->findParameter('b');
    self::assertNotNull($paramInfo);
    self::assertSame('b', $paramInfo->getName());

    $paramVisitor = $memberVisitor->addMethod('f1', []);
    $paramVisitor->addParameter('x', []);
    $paramVisitor->markAsComplete();
    self::assertNotNull($methodInfo = $classInfo->findMethod('f1'));
    self::assertNotNull($methodInfo->findParameter('x'));

    $memberVisitor->addMethod('f2', []);
    self::assertNotNull($classInfo->findMethod('f2'));

    $memberVisitor->addMethod('f3', []);
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
