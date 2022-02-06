<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests;

use Donquixote\QuickAttributes\Util\TokenPositionUtil;
use PHPUnit\Framework\TestCase;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 *   See https://github.com/sebastianbergmann/phpunit/pull/4795
 * }
 */
class TokenPositionUtilTest extends TestCase {

  public function testLineChrPos(): void {
    $php = <<<'EOT'
<?php

function foo() {}
EOT;
    /** @var list<string|array{int, string, int}> $tokens */
    $tokens = \token_get_all($php);
    $tokens[] = '#';
    $report = '';
    foreach ($tokens as $pos => $token) {
      if ($token === '#') {
        break;
      }
      $linechrpos = TokenPositionUtil::formatLineChrPos($tokens, $pos);
      [$line, $chrpos] = TokenPositionUtil::findLineChrPos($tokens, $pos);
      self::assertSame($linechrpos, "$line:$chrpos");
      $line = TokenPositionUtil::findLineNumber($tokens, $pos);
      $chrpos = TokenPositionUtil::findChrPos($tokens, $pos);
      self::assertSame($linechrpos, "$line:$chrpos");
      if (\is_array($token)) {
        self::assertSame($line, $token[2]);
        /** @noinspection JsonEncodingApiUsageInspection */
        $report .= $linechrpos
          . ': ' . \json_encode($token[1])
          . "\n";
      }
      else {
        $report .= $linechrpos
          . ': ' . $token
          . "\n";
      }
    }
    self::assertSame(<<<'EOT'
1:1: "<?php\n"
2:1: "\n"
3:1: "function"
3:9: " "
3:10: "foo"
3:13: (
3:14: )
3:15: " "
3:16: {
3:17: }

EOT
, $report);
  }

}
