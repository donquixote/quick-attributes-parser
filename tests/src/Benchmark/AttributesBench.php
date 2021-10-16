<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests\Benchmark;

use Donquixote\QuickAttributes\AttributeCommentParser\AttributeCommentParser;
use Donquixote\QuickAttributes\Tests\Fixture\CMinimal;
use PhpBench\Benchmark\Metadata\Annotations\Groups;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use PhpBench\Benchmark\Metadata\Annotations\Warmup;

/**
 * @Warmup(1)
 */
class AttributesBench {

  /**
   * @Revs(10)
   * @Iterations(5)
   * @Groups("attrComment")
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   * @throws \ReflectionException
   */
  public function benchParseAttributes(): void {
    /** @psalm-suppress InvalidLiteralArgument */
    $attrCommentParser = (new AttributeCommentParser())
      ->withContext(
        \substr(
          CMinimal::class,
          0,
          (int) \strrpos(CMinimal::class, '\\')),
        [
          'A' => 'N\\M\\A',
        ],
        CMinimal::class);
    foreach ([
      '#[A(5)] #[B(7, x: A::class), C(Other::class, [array(5 => 7, 9)])]',
      '#[A(5)] #[B(x: 7), C()]',
      '#[A()]',
      '#[A(self::V, CMinimal::U, \Donquixote\QuickAttributes\Tests\Fixture\CMinimal::U, CMinimal::class)]',
    ] as $comment) {
      foreach ($attrCommentParser->parse($comment . "\n") as $rawAttr) {
        $rawAttr->getArguments();
      }
    }
  }

}
