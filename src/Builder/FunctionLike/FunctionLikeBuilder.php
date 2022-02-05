<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\FunctionLike;

use Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilderInterface;
use Donquixote\QuickAttributes\Builder\Parameters\ParametersBuilderInterface;

class FunctionLikeBuilder implements FunctionLikeBuilderInterface {

  /**
   * @var \Donquixote\QuickAttributes\Builder\Parameters\ParametersBuilderInterface
   */
  private ParametersBuilderInterface $parametersBuilder;

  /**
   * @var \Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilderInterface
   */
  private AttributesBuilderInterface $attributesBuilder;

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilderInterface $attributesBuilder
   * @param \Donquixote\QuickAttributes\Builder\Parameters\ParametersBuilderInterface $parametersBuilder
   */
  public function __construct(AttributesBuilderInterface $attributesBuilder, ParametersBuilderInterface $parametersBuilder) {
    $this->parametersBuilder = $parametersBuilder;
    $this->attributesBuilder = $attributesBuilder;
  }

  public function buildAttributes(): AttributesBuilderInterface {
    return $this->attributesBuilder;
  }

  public function buildParameters(): ParametersBuilderInterface {
    return $this->parametersBuilder;
  }

}
