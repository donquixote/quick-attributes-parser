<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\File;

use Donquixote\QuickAttributes\FileTokens\FileTokens_Common;
use Donquixote\QuickAttributes\FileTokens\FileTokensInterface;
use Donquixote\QuickAttributes\Parser\FileTokenParser;
use Donquixote\QuickAttributes\Parser\FileTokenParserInterface;
use Donquixote\QuickAttributes\SymbolVisitor\SymbolVisitorBase;

class FileInfo extends SymbolVisitorBase {

  /**
   * @param string $file
   * @param \Donquixote\QuickAttributes\Parser\FileTokenParserInterface|null $parser
   *
   * @return self
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public static function fromFile(string $file, FileTokenParserInterface $parser = null): self {
    return self::fromFileTokens(
      FileTokens_Common::fromFile($file),
      $parser);
  }

  /**
   * @param string $file
   * @param \Donquixote\QuickAttributes\Parser\FileTokenParserInterface|null $parser
   *
   * @return self|null
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public static function fromUnknownFile(string $file, FileTokenParserInterface $parser = null): ?self {
    $tokens = FileTokens_Common::fromUnknownFile($file);
    if ($tokens === null) {
      return null;
    }
    return self::fromFileTokens(
      $tokens,
      $parser);
  }

  /**
   * @param string $php
   * @param string|null $expectedClassShortname
   * @param \Donquixote\QuickAttributes\Parser\FileTokenParserInterface|null $parser
   *
   * @return self
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public static function fromPhpSnippet(string $php, string $expectedClassShortname = null, FileTokenParserInterface $parser = null): self {
    return self::fromFileTokens(
      new FileTokens_Common($php, $expectedClassShortname),
      $parser);
  }

  /**
   * @param \Donquixote\QuickAttributes\FileTokens\FileTokensInterface $fileTokens
   * @param \Donquixote\QuickAttributes\Parser\FileTokenParserInterface|null $parser
   *
   * @return self
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public static function fromFileTokens(FileTokensInterface $fileTokens, FileTokenParserInterface $parser = null): self {
    return new self($parser ?? FileTokenParser::create(), $fileTokens);
  }
}
