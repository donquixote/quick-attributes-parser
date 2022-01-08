<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\FunctionLike;

use Donquixote\QuickAttributes\Lookup\LookupInterface;
use Donquixote\QuickAttributes\SymbolInfo\Shared\GlobalSymbolInfoInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class FunctionInfo extends FunctionInfoBase implements GlobalSymbolInfoInterface, FunctionInfoInterface {

  /**
   * @var array<string, string>
   */
  private array $imports = [];

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\Lookup\LookupInterface $lookup
   * @param string $name
   * @param string $id
   *
   * @return static|null
   */
  public static function create(LookupInterface $lookup, string $name, string $id): ?self {
    $imports = $lookup->keyGetImports($name . '()');
    if ($imports === null) {
      return null;
    }
    $instance = parent::create($lookup, $name, $id);
    if ($instance === null) {
      throw new \RuntimeException('Attributes found, but no imports?');
    }
    $instance->imports = $imports;
    return $instance;
  }

  public function getId(): string {
    return $this->getName() . '()';
  }

  /**
   * @return array<string, string>
   */
  public function getImports(): ?array {
    return $this->imports;
  }

}
