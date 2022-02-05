<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\ClassBody;

use Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilderInterface;
use Donquixote\QuickAttributes\Builder\FunctionLike\FunctionLikeBuilderInterface;

interface ClassBodyBuilderInterface {

  /**
   * @param string $name
   *
   * @return \Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilderInterface
   */
  public function addProperty(string $name): AttributesBuilderInterface;

  /**
   * @param string $name
   */
  public function addConstant(string $name): AttributesBuilderInterface;

  /**
   * @param string $name
   *
   * @return \Donquixote\QuickAttributes\Builder\FunctionLike\FunctionLikeBuilderInterface
   */
  public function addMethod(string $name): FunctionLikeBuilderInterface;

  public function markAsComplete(): void;

}
