<?php

/**
 * @file
 */

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\Value;

interface ArrayBuilderInterface {

  /**
   * @param array-key|null $key
   *
   * @return ValueBuilderInterface
   */
  public function add($key = null): ValueBuilderInterface;

  /**
   * @return array{ValueBuilderInterface, ValueBuilderInterface}
   */
  public function addKeyValue(): array;

  /**
   * Uses the last array value as a key, and opens a new builder for the value.
   *
   * @return ValueBuilderInterface
   */
  public function mapTo(): ValueBuilderInterface;

}
