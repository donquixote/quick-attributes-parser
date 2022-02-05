<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\File;

use Donquixote\QuickAttributes\Builder\ClassLike\ClassLikeBuilderInterface;

class FileBuilder_CollectClassHeadsOnly extends FileBuilder_NoOp {

  /**
   * @var array<class-string, true>
   */
  private $classes = [];

  /**
   * @return array<class-string, true>
   */
  public function getClasses(): array {
    return $this->classes;
  }

  /**
   * @inheritDoc
   */
  public function addClass(string $name, array $imports): ClassLikeBuilderInterface {
    $this->classes[$name] = true;
    return parent::addClass($name, $imports);
  }

}
