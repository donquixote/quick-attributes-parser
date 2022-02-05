<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\ClassLike;

use Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilderInterface;
use Donquixote\QuickAttributes\Builder\ClassBody\ClassBodyBuilderInterface;

class ClassLikeBuilder implements ClassLikeBuilderInterface {

  /**
   * @var \Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilderInterface
   */
  private AttributesBuilderInterface $attributesBuilder;

  /**
   * @var \Donquixote\QuickAttributes\Builder\ClassBody\ClassBodyBuilderInterface
   */
  private ClassBodyBuilderInterface $bodyBuilder;

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilderInterface $attributesBuilder
   * @param \Donquixote\QuickAttributes\Builder\ClassBody\ClassBodyBuilderInterface $bodyBuilder
   */
  public function __construct(AttributesBuilderInterface $attributesBuilder, ClassBodyBuilderInterface $bodyBuilder) {
    $this->attributesBuilder = $attributesBuilder;
    $this->bodyBuilder = $bodyBuilder;
  }

  public function buildAttributes(): AttributesBuilderInterface {
    return $this->attributesBuilder;
  }

  public function buildClassBody(): ClassBodyBuilderInterface {
    return $this->bodyBuilder;
  }

}
