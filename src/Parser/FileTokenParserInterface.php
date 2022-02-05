<?php

/**
 * @file
 */

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Parser;

use Donquixote\QuickAttributes\Builder\File\FileBuilderInterface;
use Donquixote\QuickAttributes\FileTokens\FileTokensInterface;

interface FileTokenParserInterface {

  /**
   * @param \Donquixote\QuickAttributes\FileTokens\FileTokensInterface $fileTokens
   * @param \Donquixote\QuickAttributes\Builder\File\FileBuilderInterface $fileBuilder
   *
   * @return \Iterator<int, true>
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function parseFileTokens(FileTokensInterface $fileTokens, FileBuilderInterface $fileBuilder): \Iterator;

}
