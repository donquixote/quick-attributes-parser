<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Registry;

use Donquixote\QuickAttributes\FileTokens\FileTokensInterface;
use Donquixote\QuickAttributes\Lookup\Lookup_LazyLoadDecorator;
use Donquixote\QuickAttributes\Parser\FileParser;
use Donquixote\QuickAttributes\SymbolInfo\ClassInfo;
use Donquixote\QuickAttributes\SymbolInfo\FunctionInfo;
use Donquixote\QuickAttributes\SymbolVisitor\SymbolVisitor_CollectInfo;

class FileTokensReader {

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
   * @param \Donquixote\QuickAttributes\FileTokens\FileTokensInterface $fileTokens
   *
   * @return \Iterator<string, \Donquixote\QuickAttributes\SymbolInfo\GlobalSymbolInfoInterface>
   * @psalm-return \Iterator<string, FunctionInfo|ClassInfo>
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function read(FileTokensInterface $fileTokens): \Iterator {
    $visitor = new SymbolVisitor_CollectInfo();
    $it = $this->parser->parseFileTokens($fileTokens, $visitor);
    $lookup = new Lookup_LazyLoadDecorator($visitor, $it);
    foreach ($lookup->readToplevelNames() as $name) {
      if (\substr($name, -2) === '()') {
        // Found a function.
        $function = \substr($name, 0, -2);
        yield $name => FunctionInfo::createExpected(
          $lookup,
          $function,
          $function . '()');
      }
      else {
        // Found a class, interface or trait.
        /** @var class-string $class */
        $class = $name;
        yield $name => ClassInfo::createExpected(
          $lookup,
          $class,
          $class);
      }
    }
  }

}
