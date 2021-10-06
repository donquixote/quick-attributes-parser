<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests\Attribute;

#[\Attribute]
class A0 {

  public string $name;

  /**
   * Constructor.
   *
   * @param string $name
   */
  public function __construct(string $name) {
    $this->name = $name;
  }
}
