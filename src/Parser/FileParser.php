<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Parser;

use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\FileTokens\FileTokens_Common;
use Donquixote\QuickAttributes\SymbolVisitor\SymbolVisitorInterface;

class FileParser {

  /**
   * @var \Donquixote\QuickAttributes\Parser\FileTokenParserInterface
   */
  private FileTokenParserInterface $fileTokenParser;

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\Parser\FileTokenParserInterface $fileTokenParser
   */
  public function __construct(FileTokenParserInterface $fileTokenParser) {
    $this->fileTokenParser = $fileTokenParser;
  }

  /**
   * @return self
   */
  public static function create(): self {
    return new self(FileTokenParser::create());
  }

  /**
   * @param string $file
   * @param \Donquixote\QuickAttributes\SymbolVisitor\SymbolVisitorInterface $visitor
   *
   * @return \Iterator<int, true>
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function parseFile(string $file, SymbolVisitorInterface $visitor): \Iterator {
    try {
      $fileTokens = FileTokens_Common::fromFile($file);
      yield from $this->fileTokenParser->parseFileTokens($fileTokens, $visitor);
    }
    catch (ParserException $e) {  // @codeCoverageIgnore
      $e->setSourceFile($file);  // @codeCoverageIgnore
      throw $e;  // @codeCoverageIgnore
    }
  }

}
