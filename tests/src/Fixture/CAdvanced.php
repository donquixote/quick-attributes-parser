<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests\Fixture;

use Donquixote\QuickAttributes\Tests\Attribute\A0;
use Donquixote\QuickAttributes\Tests\Attribute\A1;
use Donquixote\QuickAttributes\Tests\Attribute\Sub\A0 as SubA0;

#[A0('class')]
#[A1(), SubA0]
abstract class CAdvanced {

  #[A0('const U')]
  #[A1]
  const U = 'u';

  const V = 'v';

  public int $x;

  #[A0('property $y')]
  public string $y;

  #[A0('constructor')]
  #[A1()]
  public function __construct(
    int $x,
    #[A0('param y')]
    #[A1]
    string $y,
    ...$args
  ) {
    $this->x = $x;
    $this->y = $y;
  }

  public function f() {}

  abstract protected function abs();

}
