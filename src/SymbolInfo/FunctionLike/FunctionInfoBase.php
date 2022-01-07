<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\FunctionLike;

use Donquixote\QuickAttributes\Lookup\LookupInterface;
use Donquixote\QuickAttributes\SymbolInfo\Parameter\ParamInfo;
use Donquixote\QuickAttributes\SymbolInfo\Parameter\ParamInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\Shared\SymbolInfoBase;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
abstract class FunctionInfoBase extends SymbolInfoBase implements FunctionLikeInfoInterface {

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

  public function findParameter(string $name): ?ParamInfoInterface {
    \assert(\preg_match('@^\w+$@', $name), $name);
    return ParamInfo::create(
      $this->lookup,
      $name,
      $this->function . '($' . $name . ')');
  }

  /**
   * @return \Iterator<int, ParamInfoInterface>
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
