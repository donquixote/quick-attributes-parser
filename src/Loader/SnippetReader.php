<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Loader;

use Donquixote\QuickAttributes\Parser\FileTokenParser;
use Donquixote\QuickAttributes\Parser\FileTokenParserInterface;
use Donquixote\QuickAttributes\SymbolInfo\File\FileInfo;
use Donquixote\QuickAttributes\SymbolInfo\File\FileInfoInterface;

class SnippetReader implements SnippetReaderInterface {

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
   * @param string $php
   * @param string|null $expectedClassShortname
   *
   * @return \Donquixote\QuickAttributes\SymbolInfo\File\FileInfoInterface
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function loadPhpSnippet(string $php, string $expectedClassShortname = null): FileInfoInterface {
    return FileInfo::fromPhpSnippet($php, $expectedClassShortname, $this->parser);
  }

}
