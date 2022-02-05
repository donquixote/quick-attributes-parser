<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\RawAttribute;

use Donquixote\QuickAttributes\ValueExpression\ArgumentsInterface;

/**
 * @template-covariant T as object
 *
 * @template-implements RawAttributeInterface<T>
 */
class RawAttribute_Arguments implements RawAttributeInterface {

  /**
   * @var class-string<T>
   */
  private string $name;

  /**
   * @var \Donquixote\QuickAttributes\ValueExpression\ArgumentsInterface
   */
  private ArgumentsInterface $arguments;

  /**
   * Constructor.
   *
   * @param class-string<T> $name
   * @param \Donquixote\QuickAttributes\ValueExpression\ArgumentsInterface $arguments
   */
  public function __construct(string $name, ArgumentsInterface $arguments) {
    $this->name = $name;
    $this->arguments = $arguments;
  }

  /**
   * @return class-string<T>
   */
  public function getName(): string {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getArguments(): array {
    return $this->arguments->getArguments();
  }

}
