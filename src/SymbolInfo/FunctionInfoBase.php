<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo;

use Donquixote\QuickAttributes\Lookup\LookupInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
abstract class FunctionInfoBase implements SymbolInfoInterface {

  /**
   * @var \Donquixote\QuickAttributes\Lookup\LookupInterface
   */
  protected LookupInterface $lookup;

  /**
   * @var string
   */
  protected string $function;

  /**
   * @var list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface>
   */
  private array $attributes;

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\Lookup\LookupInterface $lookup
   * @param string $function
   *
   * @return static
   *
   * @throws \RuntimeException
   */
  public static function createExpected(LookupInterface $lookup, string $function): ?self {
    $instance = static::create($lookup, $function);
    if ($instance === null) {
      throw new \RuntimeException('Function not found.');
    }
    return $instance;
  }

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\Lookup\LookupInterface $lookup
   * @param string $function
   *
   * @return static|null
   */
  public static function create(LookupInterface $lookup, string $function): ?self {
    $attributes = $lookup->keyGetAttributes($function . '()');
    if ($attributes === null) {
      return null;
    }
    $instance = new static();
    $instance->lookup = $lookup;
    $instance->function = $function;
    $instance->attributes = $attributes;
    return $instance;
  }

  final private function __construct() {}

  public function getAttributes(): array {
    return $this->attributes;
  }

  /**
   * @param string $param
   *
   * @return list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface>|null
   */
  public function paramGetAttributes(string $param): ?array {
    \assert(\preg_match('@^\w+$@', $param), $param);
    return $this->lookup->keyGetAttributes($this->function . '($' . $param . ')');
  }

  /**
   * @return \Iterator<string, list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface>>
   */
  public function readParameters(): \Iterator {
    foreach ($this->lookup->keyReadChildNames($this->function . '()') as $param) {
      \assert(\preg_match('@^\w+$@', $param), $param);
      $attributes = $this->paramGetAttributes($param);
      \assert($attributes !== null);
      yield $param => $attributes;
    }
  }

}
