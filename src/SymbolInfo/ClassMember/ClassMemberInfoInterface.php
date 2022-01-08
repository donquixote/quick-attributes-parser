<?php

/**
 * @file
 */

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\ClassMember;

use Donquixote\QuickAttributes\SymbolInfo\Shared\AttributesInfoInterface;

interface ClassMemberInfoInterface extends AttributesInfoInterface {

  public function getName(): string;

  /**
   * Gets an id to distinguish from other class members.
   *
   * @return string
   */
  public function getMemberId(): string;

}
