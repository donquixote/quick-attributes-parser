<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\FunctionLike;

use Donquixote\QuickAttributes\SymbolInfo\ClassLike\FunctionInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\ClassLike\GlobalSymbolInfoBase;
use Donquixote\QuickAttributes\SymbolVisitor\FunctionLike\ParamVisitorAndInfoTrait;
use Donquixote\QuickAttributes\SymbolVisitor\FunctionLike\ParamVisitorInterface;

class FunctionInfo extends GlobalSymbolInfoBase implements FunctionInfoInterface, ParamVisitorInterface {

  use ParamVisitorAndInfoTrait;

  /**
   * @var callable-string
   */
  private string $name;

  /**
   * Constructor.
   *
   * @param callable-string $name
   * @param list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface> $attributes
   * @param array<string, string> $imports
   * @param \Iterator<int, true> $it
   */
  public function __construct(string $name, array $attributes, array $imports, \Iterator $it) {
    parent::__construct($imports, $attributes);
    $this->name = $name;
    $this->it = $it;
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
