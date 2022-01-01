<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Value;

class RawSymbolInfo {

  /**
   * @var array<string, string>|null
   */
  private ?array $imports = null;

  /**
   * @var string[]
   */
  private array $attributeComments = [];

  /**
   * Private constructor.
   */
  private function __construct() {}

  /**
   * @param string[] $attributeComments
   *
   * @return self
   */
  public static function forInnerSymbol(array $attributeComments): self {
    $instance = new self();
    $instance->attributeComments = $attributeComments;
    return $instance;
  }

  /**
   * @param string[] $attributeComments
   * @param array<string, string> $imports
   *
   * @return self
   */
  public static function forTopLevelSymbol(array $attributeComments, array $imports): self {
    $instance = new self();
    $instance->attributeComments = $attributeComments;
    $instance->imports = $imports;
    return $instance;
  }

  /**
   * @param array<string, string> $imports
   *
   * @return static
   */
  public function withImports(array $imports): self {
    $clone = clone $this;
    $clone->imports = $imports;
    return $clone;
  }

  /**
   * Imports declared for the global symbol.
   *
   * @return array<string, string>|null
   *   Format (class, namespace): $[$alias] = $qcn.
   *   Format (function): $["function $alias"] = $qcn.
   *   Format (constant): $["const $alias"] = $qcn.
   */
  public function getImports(): ?array {
    return $this->imports;
  }

  /**
   * Attributes that are interpreted as comments in PHP < 8.
   *
   * In PHP 8+, this will be empty.
   *
   * @return string[]
   *   Format: $[] = "#[...]".
   */
  public function getAttributeComments(): array {
    return $this->attributeComments;
  }

}
