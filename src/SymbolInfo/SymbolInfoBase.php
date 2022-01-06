<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo;

use Donquixote\QuickAttributes\Lookup\LookupInterface;

abstract class SymbolInfoBase implements SymbolInfoInterface {

  /**
   * @var list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface>
   */
  private array $attributes = [];

  private string $name = '?';

  private string $id = '?';

  final private function __construct() {}

  /**
   * @param \Donquixote\QuickAttributes\Lookup\LookupInterface $lookup
   * @param string $name
   * @param string $id
   *
   * @return static
   *
   */
  public static function createExpected(LookupInterface $lookup, string $name, string $id): self {
    $instance = static::create($lookup, $name, $id);
    if ($instance === null) {
      throw new \RuntimeException("Symbol $id / $name not found.");
    }
    return $instance;
  }

  /**
   * @param \Donquixote\QuickAttributes\Lookup\LookupInterface $lookup
   * @param string $name
   * @param string $id
   *
   * @return static|null
   */
  public static function create(LookupInterface $lookup, string $name, string $id): ?self {
    $attributes = $lookup->keyGetAttributes($id);
    if ($attributes === null) {
      return null;
    }
    $instance = new static();
    $instance->name = $name;
    $instance->id = $id;
    $instance->attributes = $attributes;
    return $instance;
  }

  public function getName(): string {
    return $this->name;
  }

  public function getId(): string {
    return $this->id;
  }

  public function getAttributes(): array {
    return $this->attributes;
  }

}
