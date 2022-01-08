<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SnippetReader;

use Donquixote\QuickAttributes\Parser\FileParser;
use Donquixote\QuickAttributes\SymbolInfo\FileInfo;

class SnippetReader implements SnippetReaderInterface {

  private FileParser $parser;

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\Parser\FileParser $parser
   */
  public function __construct(FileParser $parser) {
    $this->parser = $parser;
  }

  public static function create(): self {
    return new self(FileParser::create());
  }

  /**
   * @param string $php
   * @param string|null $expectedClassShortname
   *
   * @return \Donquixote\QuickAttributes\SymbolInfo\FileInfo
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function loadPhpSnippet(string $php, string $expectedClassShortname = null): FileInfo {
    return FileInfo::fromPhpSnippet($php, $expectedClassShortname, $this->parser);
  }

}
