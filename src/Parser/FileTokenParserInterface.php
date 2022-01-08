<?php

/**
 * @file
 */

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Parser;

use Donquixote\QuickAttributes\FileTokens\FileTokensInterface;
use Donquixote\QuickAttributes\SymbolVisitor\SymbolVisitorInterface;

interface FileTokenParserInterface {

  /**
   * @param \Donquixote\QuickAttributes\FileTokens\FileTokensInterface $fileTokens
   * @param \Donquixote\QuickAttributes\SymbolVisitor\SymbolVisitorInterface $visitor
   *
   * @return \Iterator<int, true>
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function parseFileTokens(FileTokensInterface $fileTokens, SymbolVisitorInterface $visitor): \Iterator;

}
