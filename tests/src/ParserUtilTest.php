<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

use Donquixote\QuickAttributes\Util\ParserUtil;
use PHPUnit\Framework\TestCase;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 *   See https://github.com/sebastianbergmann/phpunit/pull/4795
 */
class ParserUtilTest extends TestCase {

  /**
   * @throws \Donquixote\QuickAttributes\Exception\SyntaxException
   */
  public function testSkipSubtree(): void {
    $tokens = [
      '{',
      '}',
      '#',
    ];
    $i = 0;
    ParserUtil::skipSubtree($tokens, $i);
    self::assertSame(1, $i);
  }

}
