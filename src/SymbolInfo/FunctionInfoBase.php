<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo;

use Donquixote\QuickAttributes\Lookup\LookupInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
abstract class FunctionInfoBase extends SymbolInfoBase {

  /**
   * @var \Donquixote\QuickAttributes\Lookup\LookupInterface
   */
  protected LookupInterface $lookup;

  protected string $function = '?';

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
    $instance = parent::create($lookup, $name, $id);
    if ($instance === null) {
      return null;
    }
    $instance->lookup = $lookup;
    $instance->function = \substr($id, 0, -2);
    return $instance;
  }

  public function getParameter(string $param): ?ParamInfo {
    \assert(\preg_match('@^\w+$@', $param), $param);
    return ParamInfo::create(
      $this->lookup,
      $param,
      $this->function . '($' . $param . ')');
  }

  /**
   * @return \Iterator<int, ParamInfo>
   */
  public function readParameters(): \Iterator {
    foreach ($this->lookup->keyReadChildNames($this->function . '()') as $param) {
      \assert(\preg_match('@^\w+$@', $param), $param);
      yield ParamInfo::createExpected(
        $this->lookup,
        $param,
        $this->function . '($' . $param . ')');
    }
  }

}
