<?php

/**
 * @file
 */

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Loader;

use Donquixote\QuickAttributes\SymbolInfo\File\FileInfoInterface;

interface SnippetReaderInterface {

  /**
   * @param string $php
   *   PHP snippet starting with '<?php'.
   * @param string|null $expectedClassShortname
   *
   * @return \Donquixote\QuickAttributes\SymbolInfo\File\FileInfoInterface
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function loadPhpSnippet(string $php, string $expectedClassShortname = null): FileInfoInterface;

}
