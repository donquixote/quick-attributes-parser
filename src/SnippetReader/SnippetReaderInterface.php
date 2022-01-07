<?php

/**
 * @file
 */

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SnippetReader;

use Donquixote\QuickAttributes\SymbolInfo\File\FileInfo;

interface SnippetReaderInterface {

  /**
   * @param string $php
   *   PHP snippet starting with '<?php'.
   * @param string|null $expectedClassShortname
   *
   * @return \Donquixote\QuickAttributes\SymbolInfo\File\FileInfo
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function loadPhpSnippet(string $php, string $expectedClassShortname = null): FileInfo;

}
