<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Registry;

use Donquixote\QuickAttributes\Parser\FileTokenParser;
use Donquixote\QuickAttributes\Parser\FileTokenParserInterface;
use Donquixote\QuickAttributes\SymbolInfo\File\FileInfo;
use Donquixote\QuickAttributes\SymbolInfo\File\FileInfoInterface;

class FileInfoLoader {

  private FileTokenParserInterface $parser;

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\Parser\FileTokenParserInterface $parser
   */
  public function __construct(FileTokenParserInterface $parser) {
    $this->parser = $parser;
  }

  public static function create(): self {
    return new self(FileTokenParser::create());
  }

  /**
   * @param string $file
   *
   * @return \Donquixote\QuickAttributes\SymbolInfo\File\FileInfoInterface
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function loadFile(string $file): FileInfoInterface {
    return FileInfo::fromFile($file, $this->parser);
  }

  /**
   * @param string $file
   *
   * @return FileInfoInterface|null
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function loadUnknownFile(string $file): ?FileInfoInterface {
    return FileInfo::fromFile($file, $this->parser);
  }

}
