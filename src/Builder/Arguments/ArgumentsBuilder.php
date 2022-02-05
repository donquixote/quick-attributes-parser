<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\Arguments;

use Donquixote\QuickAttributes\Builder\Value\ValueBuilder;
use Donquixote\QuickAttributes\Builder\Value\ValueBuilderInterface;
use Donquixote\QuickAttributes\ValueExpression\ArgumentsInterface;

class ArgumentsBuilder implements ArgumentsBuilderInterface, ArgumentsInterface {

  /**
   * @var \Donquixote\QuickAttributes\ValueExpression\ValueExpressionInterface[]
   */
  private array $arguments = [];

  public static function start(): self {
    return new self();
  }

  /**
   * @param string|null $key
   *
   * @return \Donquixote\QuickAttributes\Builder\Value\ValueBuilderInterface
   */
  public function addArgument(string $key = null): ValueBuilderInterface {
    $arg = ValueBuilder::start();
    if ($key === null) {
      $this->arguments[] = $arg;
    }
    elseif (\is_numeric($key)) {
      throw new \InvalidArgumentException('Parameter name cannot be numeric');
    }
    else {
      $this->arguments[$key] = $arg;
    }
    return $arg;
  }

  public function getArguments(): array {
    $values = [];
    foreach ($this->arguments as $k => $arg) {
      /** @psalm-suppress MixedAssignment */
      $values[$k] = $arg->getValue();
    }
    return $values;
  }

}
