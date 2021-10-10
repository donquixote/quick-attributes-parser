<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\RawAttribute;

/**
 * @template T
 */
class RawAttribute_Fixed implements RawAttributeInterface {

  /**
   * @var class-string
   */
  private string $name;

  private array $arguments;

  /**
   * Constructor.
   *
   * @param class-string $name
   * @param array $arguments
   */
  public function __construct(string $name, array $arguments) {
    $this->name = $name;
    $this->arguments = $arguments;
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getArguments(): array {
    return $this->arguments;
  }

}
