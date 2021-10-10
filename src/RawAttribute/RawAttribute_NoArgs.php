<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\RawAttribute;

/**
 * @template T as object
 */
class RawAttribute_NoArgs implements RawAttributeInterface {

  /**
   * @var class-string<T>
   */
  private string $name;

  /**
   * Constructor.
   *
   * @param class-string<T> $name
   */
  public function __construct(string $name) {
    $this->name = $name;
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
    return [];
  }

}
