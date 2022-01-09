<?php

/**
 * @file
 */

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Loader;

use Donquixote\QuickAttributes\SymbolInfo\File\FileInfoInterface;

interface FileInfoLoaderInterface {

  /**
   * @param string $file
   *
   * @return \Donquixote\QuickAttributes\SymbolInfo\File\FileInfoInterface
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function loadFile(string $file): FileInfoInterface;

  /**
   * @param string $file
   *
   * @return FileInfoInterface|null
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function loadUnknownFile(string $file): ?FileInfoInterface;

}
