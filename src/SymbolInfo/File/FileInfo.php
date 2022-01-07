<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\File;

use Donquixote\QuickAttributes\FileTokens\FileTokens_Common;
use Donquixote\QuickAttributes\FileTokens\FileTokensInterface;
use Donquixote\QuickAttributes\Parser\FileParser;
use Donquixote\QuickAttributes\SymbolVisitor\File\SymbolVisitorBase;

class FileInfo extends SymbolVisitorBase {

  /**
   * @param string $file
   * @param \Donquixote\QuickAttributes\Parser\FileParser|null $parser
   *
   * @return FileInfoInterface
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public static function fromKnownFile(string $file, FileParser $parser = null): FileInfoInterface {
    return self::fromFileTokens(
      FileTokens_Common::fromKnownFile($file),
      $parser ?? FileParser::create());
  }

  /**
   * @param string $file
   * @param FileParser|null $parser
   *
   * @return FileInfoInterface|null
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public static function fromFile(string $file, FileParser $parser = null): ?FileInfoInterface {
    $tokens = FileTokens_Common::fromFile($file);
    if ($tokens === null) {
      return null;
    }
    return self::fromFileTokens(
      $tokens,
      $parser ?? FileParser::create());
  }

  /**
   * @param \Donquixote\QuickAttributes\FileTokens\FileTokensInterface $fileTokens
   * @param \Donquixote\QuickAttributes\Parser\FileParser $parser
   *
   * @return FileInfoInterface
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public static function fromFileTokens(FileTokensInterface $fileTokens, FileParser $parser): FileInfoInterface {
    return new self($parser, $fileTokens);
  }

}
