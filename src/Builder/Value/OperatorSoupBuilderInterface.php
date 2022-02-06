<?php

/**
 * @file
 */

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\Value;

interface OperatorSoupBuilderInterface {

  /**
   * @param string $operator
   *
   * @return ValueBuilderInterface
   */
  public function add(string $operator): ValueBuilderInterface;

  /**
   * @return \Donquixote\QuickAttributes\Builder\Value\ValueBuilderInterface
   */
  public function addArrayOffset(): ValueBuilderInterface;

}
