<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Registry;

use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\FileTokens\FileTokens_Common;
use Donquixote\QuickAttributes\SymbolInfo\ClassInfo;
use Donquixote\QuickAttributes\SymbolInfo\FunctionInfo;

class FileReader {

  /**
   * @var \Donquixote\QuickAttributes\Registry\FileTokensReader
   */
  private FileTokensReader $fileTokensReader;

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\Registry\FileTokensReader $fileTokensReader
   */
  public function __construct(FileTokensReader $fileTokensReader) {
    $this->fileTokensReader = $fileTokensReader;
  }

  public static function create(): self {
    return new self(FileTokensReader::create());
  }

  /**
   * @param string $file
   *
   * @return \Iterator<string, FunctionInfo|ClassInfo>
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function read(string $file): \Iterator {
    try {
      $fileTokens = FileTokens_Common::fromFile($file);
      yield from $this->fileTokensReader->read($fileTokens);
    }
    catch (ParserException $e) {  // @codeCoverageIgnore
      $e->setSourceFile($file);  // @codeCoverageIgnore
      throw $e;  // @codeCoverageIgnore
    }
  }

}
