<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

use Donquixote\QuickAttributes\FileTokens\FileTokens_Common;
use Donquixote\QuickAttributes\Util\ParserAssertUtil;
use PHPUnit\Framework\TestCase;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 *   See https://github.com/sebastianbergmann/phpunit/pull/4795
 */
class FileTokensTest extends TestCase {

  /**
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function testSelfClass(): void {
    $tokens = FileTokens_Common::fromFile(__FILE__);
    $head = $tokens->getClassFileHead();
    self::assertNotNull($head);
    ParserAssertUtil::expect($head, -1, '#');
    ParserAssertUtil::expect($head, -2, '{');
    $all = $tokens->getAll();
    ParserAssertUtil::expect($all, -1, '#');
  }

  /**
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function testPlainText(): void {
    $tokens = FileTokens_Common::fromFile(\dirname(__DIR__) . '/fixtures/misc/plain-text.txt');
    $head = $tokens->getClassFileHead();
    self::assertNull($head);
    $all = $tokens->getAll();
    self::assertCount(2, $all);
    ParserAssertUtil::expect($all, 0, \T_INLINE_HTML);
    ParserAssertUtil::expect($all, 1, '#');
  }

}
