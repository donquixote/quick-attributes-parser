<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Parser;

use Donquixote\QuickAttributes\Builder\File\FileBuilderInterface;
use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\FileTokens\FileTokens_Common;

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
   * @param \Donquixote\QuickAttributes\Builder\File\FileBuilderInterface $builder
   *
   * @return \Iterator<int, true>
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function parseFile(string $file, FileBuilderInterface $builder): \Iterator {
    try {
      $fileTokens = FileTokens_Common::fromFile($file);
      yield from $this->fileTokenParser->parseFileTokens($fileTokens, $builder);
    }
    catch (ParserException $e) {  // @codeCoverageIgnore
      $e->setSourceFile($file);  // @codeCoverageIgnore
      throw $e;  // @codeCoverageIgnore
    }
  }

}
