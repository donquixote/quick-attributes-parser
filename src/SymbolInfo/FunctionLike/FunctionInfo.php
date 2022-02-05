<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\FunctionLike;

use Donquixote\QuickAttributes\SymbolInfo\Shared\AttributesInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\Shared\GlobalSymbolInfoBase;

class FunctionInfo extends GlobalSymbolInfoBase implements FunctionInfoInterface {

  use ParametersInfoDecoratorTrait;

  /**
   * @var callable-string
   */
  private string $name;

  /**
   * Constructor.
   *
   * @param callable-string $name
   * @param array<string, string> $imports
   * @param \Donquixote\QuickAttributes\SymbolInfo\Shared\AttributesInfoInterface $attributes
   * @param \Donquixote\QuickAttributes\SymbolInfo\FunctionLike\ParametersInfoInterface $parameters
   */
  public function __construct(string $name, array $imports, AttributesInfoInterface $attributes, ParametersInfoInterface $parameters) {
    parent::__construct($imports, $attributes);
    $this->name = $name;
    $this->parameters = $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return $this->name . '()';
  }

  /**
   * @return callable-string
   */
  public function getName(): string {
    return $this->name;
  }

}
