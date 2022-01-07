<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolVisitor\FunctionLike;

use Donquixote\QuickAttributes\SymbolInfo\Parameter\ParamInfo;
use Donquixote\QuickAttributes\SymbolInfo\Parameter\ParamInfoInterface;

/**
 * @see \Donquixote\QuickAttributes\SymbolVisitor\FunctionLike\ParamVisitorInterface
 * @see \Donquixote\QuickAttributes\SymbolInfo\FunctionLike\ParametersInfoInterface
 */
trait ParamVisitorAndInfoTrait {

  /**
   * @var array<int, ParamInfoInterface>
   */
  private array $params = [];

  /**
   * @var array<string, int>
   */
  private array $paramMap = [];

  /**
   * @var bool
   */
  private bool $complete = false;

  /**
   * @var \Iterator<int, true>
   */
  private \Iterator $it;

  /**
   * @param string $name
   * @param list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface> $attributes
   */
  public function addParameter(string $name, array $attributes): void {
    $this->paramMap[$name] = \count($this->params);
    $this->params[] = new ParamInfo($name, $attributes);
  }

  public function markAsComplete(): void {
    $this->complete = true;
  }

  /**
   * @param string $name
   *
   * @return \Donquixote\QuickAttributes\SymbolInfo\Parameter\ParamInfoInterface|null
   */
  public function findParameter(string $name): ?ParamInfoInterface {
    if (null !== ($index = $this->paramMap[$name] ?? null)) {
      return $this->params[$index];
    }
    while (!$this->complete) {
      if (!$this->it->valid()) {
        throw new \RuntimeException('Parser did not complete.');
      }
      if (null !== ($index = $this->paramMap[$name] ?? null)) {
        return $this->params[$index];
      }
      $this->it->next();
    }
    return null;
  }

  /**
   * @return \Iterator<int, ParamInfoInterface>
   */
  public function readParameters(): \Iterator {
    if ($this->complete) {
      yield from $this->params;
      return;
    }
    $index = 0;
    while (true) {
      if (isset($this->params[$index])) {
        yield $this->params[$index];
        ++$index;
        continue;
      }
      /** @psalm-suppress TypeDoesNotContainType */
      if ($this->complete) {
        return;
      }
      if ($this->it->valid()) {
        $this->it->next();
      }
    }
  }

}
