<?php

/**
 * @noinspection PhpUnhandledExceptionInspection
 */

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests\Unit;

use Donquixote\QuickAttributes\Builder\Value\ValueBuilder;
use Donquixote\QuickAttributes\Tests\Fixture\CMinimal;
use Donquixote\QuickAttributes\ValueExpression\ValueExpressionInterface;
use PHPUnit\Framework\TestCase;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 *   See https://github.com/sebastianbergmann/phpunit/pull/4795
 */
class ValueBuilderTest extends TestCase {

  public function testNonInitialized(): void {
    $endpoint = ValueBuilder::start();
    $this->assertValueExpression(
      null,
      0,
      'null',
      $endpoint);
  }

  /**
   * Builds the expression `5`.
   */
  public function testScalar(): void {
    $endpoint = ValueBuilder::start();
    $endpoint->setFixedValue(5);
    $this->assertValueExpression(
      5,
      0,
      '5',
      $endpoint);
  }

  /**
   * Builds the expression `5 + 1`.
   */
  public function testFivePlusOne(): void {
    $endpoint = ValueBuilder::start();
    $node = $endpoint->setFixedValue(5);
    $node->appendBinaryOperator('+')->setFixedValue(1);
    $this->assertValueExpression(
      5 + 1,
      0,
      '(5 + 1)',
      $endpoint);
  }

  /**
   * Builds the expression `(5 + 1) * 2`.
   */
  public function testFivePlusOneTimesTwo(): void {
    $endpoint = ValueBuilder::start();
    $node = $endpoint->setFixedValue(5);
    $node->appendBinaryOperator('+')->setFixedValue(1);
    $node->close();
    $node->appendBinaryOperator('*')->setFixedValue(2);
    $this->assertValueExpression(
      (5 + 1) * 2,
      0,
      '((5 + 1) * 2)',
      $endpoint);
  }

  /**
   * Builds the expression `2 * (5 + 1)`.
   */
  public function testTwoTimesFivePlusOne(): void {
    $endpoint = ValueBuilder::start();
    $node = $endpoint->setFixedValue(2);
    $node->appendBinaryOperator('*')
      ->setFixedValue(5)
      ->appendBinaryOperator('+')
      ->setFixedValue(1);
    $this->assertValueExpression(
      2 * (5 + 1),
      0,
      '(2 * (5 + 1))',
      $endpoint);
  }

  /**
   * Builds the expression `(3 - 1) * (5 + 1)`.
   */
  public function testTwoBranchExpression(): void {
    $endpoint = ValueBuilder::start();
    $node = $endpoint->setFixedValue(3);
    $node->appendBinaryOperator('-')
      ->setFixedValue(1);
    $node->close();
    $node->appendBinaryOperator('*')
      ->setFixedValue(5)
      ->appendBinaryOperator('+')
      ->setFixedValue(1);
    $this->assertValueExpression(
      (3 - 1) * (5 + 1),
      0,
      '((3 - 1) * (5 + 1))',
      $endpoint);
  }

  /**
   * Builds the expression `3 + 5 * 2 - 7`.
   */
  public function testOperatorSoup(): void {
    $endpoint = ValueBuilder::start();
    $node = $endpoint->setFixedValue(3);
    $node->appendBinaryOperator('+')->setFixedValue(5);
    $node->appendBinaryOperator('*')->setFixedValue(2);
    $node->appendBinaryOperator('-')->setFixedValue(7);
    $this->assertValueExpression(
      3 + 5 * 2 - 7,
      0,
      '(3 + 5 * 2 - 7)',
      $endpoint);
  }

  /**
   * Builds the expression `true|false ? 'yes' : 'no'`.
   *
   * @testWith [true, "yes", "(true ? 'yes' : 'no')"]
   *           [false, "no", "(false ? 'yes' : 'no')"]
   */
  public function testTernaryOperator(bool $condition, string $value, string $string): void {
    $endpoint = ValueBuilder::start();
    $node = $endpoint->setFixedValue($condition);
    /**
     * @var \Donquixote\QuickAttributes\Builder\Value\ValueBuilderInterface $yes
     * @var \Donquixote\QuickAttributes\Builder\Value\ValueBuilderInterface $no
     * @psalm-ignore-var
     */
    [$yes, $no] = $node->appendTernaryOperator();
    $yes->setFixedValue('yes');
    $no->setFixedValue('no');
    $this->assertValueExpression(
      $value,
      0,
      $string,
      $endpoint);
  }

  public function testEmptyArray(): void {
    $endpoint = ValueBuilder::start();
    $endpoint->startArray();
    $this->assertValueExpression(
      [],
      0,
      "[]",
      $endpoint);
  }

  /**
   * Builds the expression `['a', 'b', 'c']`.
   */
  public function testSerialArray(): void {
    $endpoint = ValueBuilder::start();
    $array = $endpoint->startArray();
    $array->add()->setFixedValue('a');
    $array->add()->setFixedValue('b');
    $array->add()->setFixedValue('c');
    $this->assertValueExpression(
      ['a', 'b', 'c'],
      0,
      "['a', 'b', 'c']",
      $endpoint);
  }

  /**
   * Builds the expression `['a' => 'A', 4 => 'four', 'b' => 'B', 7]`.
   */
  public function testAssocArray(): void {
    $endpoint = ValueBuilder::start();
    $array = $endpoint->startArray();
    $array->add('a')->setFixedValue('A');
    $array->add(4)->setFixedValue('four');
    $array->add('b')->setFixedValue('B');
    $array->add()->setFixedValue(7);
    $this->assertValueExpression(
      ['a' => 'A', 4 => 'four', 'b' => 'B', 7],
      0,
      "['a' => 'A', 4 => 'four', 'b' => 'B', 7]",
      $endpoint);
  }

  /**
   * Builds the expression `['a' => 'A', '_' . 'b' => '_B', 'c' => 'C']`.
   */
  public function testAdvancedKeysArray(): void {
    $endpoint = ValueBuilder::start();
    $array = $endpoint->startArray();
    $array->add('a')->setFixedValue('A');
    /**
     * @var \Donquixote\QuickAttributes\Builder\Value\ValueBuilderInterface $key
     * @var \Donquixote\QuickAttributes\Builder\Value\ValueBuilderInterface $value
     * @psalm-ignore-var
     */
    [$key, $value] = $array->addKeyValue();
    $key->setFixedValue('_')->appendBinaryOperator('.')->setFixedValue('b');
    $value->setFixedValue('_B');
    $array->add('c')->setFixedValue('C');
    $this->assertValueExpression(
      ['a' => 'A', '_' . 'b' => '_B', 'c' => 'C'],
      0,
      "['a' => 'A', ('_' . 'b') => '_B', 'c' => 'C']",
      $endpoint);
  }

  /**
   * Builds the expression `[false => 'a']`.
   */
  public function testInvalidArrayKey(): void {
    $endpoint = ValueBuilder::start();
    $array = $endpoint->startArray();
    $this->expectException(\ReflectionException::class);
    $this->expectExceptionMessage('Invalid array key');
    /**
     * @var \Donquixote\QuickAttributes\Builder\Value\ValueBuilderInterface $key
     * @var \Donquixote\QuickAttributes\Builder\Value\ValueBuilderInterface $value
     * @psalm-ignore-var
     */
    [$key, $value] = $array->addKeyValue();
    $key->setFixedValue(false);
    $value->setFixedValue('a');
    $endpoint->getValue();
  }

  /**
   * Builds the expression `CMinimal::U`.
   */
  public function testClassConstant(): void {
    $endpoint = ValueBuilder::start();
    $endpoint->setConstant(CMinimal::class . '::U');
    $this->assertValueExpression(
      CMinimal::U,
      ValueExpressionInterface::VARIABILITY_CODE,
      '\\' . CMinimal::class . '::U',
      $endpoint);
  }

  /**
   * Builds the expression `[CMinimal::U => 'a']`.
   */
  public function testConstAsArrayKey(): void {
    $endpoint = ValueBuilder::start();
    $array = $endpoint->startArray();
    /**
     * @var \Donquixote\QuickAttributes\Builder\Value\ValueBuilderInterface $key
     * @var \Donquixote\QuickAttributes\Builder\Value\ValueBuilderInterface $value
     * @psalm-ignore-var
     */
    [$key, $value] = $array->addKeyValue();
    $key->setConstant(CMinimal::class . '::U');
    $value->setFixedValue('a');
    $this->assertValueExpression(
      [CMinimal::U => 'a'],
      ValueExpressionInterface::VARIABILITY_CODE,
      '[\\' . CMinimal::class . "::U => 'a']",
      $endpoint);
  }

  /**
   * @param mixed $value
   * @param int $variability
   * @param string $string
   * @param \Donquixote\QuickAttributes\ValueExpression\ValueExpressionInterface $expression
   *
   * @throws \ReflectionException
   */
  protected function assertValueExpression($value, int $variability, string $string, ValueExpressionInterface $expression): void {
    self::assertSame(
      [
        'value' => $value,
        'variability' => $variability,
        'string' => $string,
      ],
      $this->exportValueExpression($expression));
  }

  /**
   * @param ValueExpressionInterface $expression
   *
   * @return array
   * @throws \ReflectionException
   */
  protected function exportValueExpression(ValueExpressionInterface $expression): array {
    return [
      'value' => $expression->getValue(),
      'variability' => $expression->getVariabilityLevel(),
      'string' => $expression->__toString(),
    ];
  }

}
