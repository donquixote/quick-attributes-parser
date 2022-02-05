<?php

/**
 * @noinspection PhpUnhandledExceptionInspection
 */

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests\Unit;

use Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilder;
use Donquixote\QuickAttributes\Tests\Attribute\A0;
use Donquixote\QuickAttributes\Tests\Util\TestExportUtil;
use PHPUnit\Framework\TestCase;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 *   See https://github.com/sebastianbergmann/phpunit/pull/4795
 */
class AttributesBuilderTest extends TestCase {

  public function testNoArgs(): void {
    $attributes = AttributesBuilder::start();
    $attributes->addAttribute(A0::class);
    self::assertSame(
      [
        [
          'name' => A0::class,
          'arguments' => [],
        ],
      ],
      TestExportUtil::exportRawAttributes(
        $attributes->getAttributes()));
  }

  public function testUnnamedArgs(): void {
    $attributes = AttributesBuilder::start();
    $attribute = $attributes->addAttribute(A0::class);
    $attribute->addArgument()->setFixedValue(5);
    $attribute->addArgument()->setFixedValue('x');
    self::assertSame(
      [
        [
          'name' => A0::class,
          'arguments' => [5, 'x'],
        ],
      ],
      TestExportUtil::exportRawAttributes(
        $attributes->getAttributes()));
  }

  public function testNamedArgs(): void {
    $attributes = AttributesBuilder::start();
    $attribute = $attributes->addAttribute(A0::class);
    $attribute->addArgument()->setFixedValue(false);
    $attribute->addArgument('x')->setFixedValue('X');
    self::assertSame(
      [
        [
          'name' => A0::class,
          'arguments' => [false, 'x' => 'X'],
        ],
      ],
      TestExportUtil::exportRawAttributes(
        $attributes->getAttributes()));
  }

}
