<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\File;

use Donquixote\QuickAttributes\Builder\ClassLike\ClassLikeBuilderInterface;
use Donquixote\QuickAttributes\Builder\FunctionLike\FunctionLikeBuilderInterface;

interface FileBuilderInterface {

  /**
   * @param class-string $name
   * @param array<string, string> $imports
   *
   * @return ClassLikeBuilderInterface
   */
  public function addClass(string $name, array $imports): ClassLikeBuilderInterface;

  /**
   * @param callable-string $name
   * @param array<string, string> $imports
   *
   * @return \Donquixote\QuickAttributes\Builder\FunctionLike\FunctionLikeBuilderInterface
   */
  public function addFunction(string $name, array $imports): FunctionLikeBuilderInterface;

}
