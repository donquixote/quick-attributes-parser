<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\Value;

class ArrayBuilder_NoOp implements ArrayBuilderInterface {

  public function add($key = null): ValueBuilderInterface {
    return new ValueBuilder_NoOp();
  }

  public function addKeyValue(): array {
    return [new ValueBuilder_NoOp(), new ValueBuilder_NoOp()];
  }

  public function mapTo(): ValueBuilderInterface {
    return new ValueBuilder_NoOp();
  }

}
