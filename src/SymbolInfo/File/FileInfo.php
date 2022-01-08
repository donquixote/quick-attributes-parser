<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\File;

use Donquixote\QuickAttributes\FileTokens\FileTokens_Common;
use Donquixote\QuickAttributes\FileTokens\FileTokensInterface;
use Donquixote\QuickAttributes\Lookup\Lookup_LazyLoadDecorator;
use Donquixote\QuickAttributes\Lookup\LookupInterface;
use Donquixote\QuickAttributes\Parser\FileParser;
use Donquixote\QuickAttributes\SymbolInfo\ClassLike\ClassInfo;
use Donquixote\QuickAttributes\SymbolInfo\FunctionLike\FunctionInfo;
use Donquixote\QuickAttributes\SymbolVisitor\SymbolVisitor_CollectInfo;

class FileInfo {

  /**
   * @var \Donquixote\QuickAttributes\Lookup\LookupInterface
   */
  private LookupInterface $lookup;

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\Lookup\LookupInterface $lookup
   */
  public function __construct(LookupInterface $lookup) {
    $this->lookup = $lookup;
  }

  /**
   * @param string $file
   * @param \Donquixote\QuickAttributes\Parser\FileParser|null $parser
   *
   * @return self
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public static function fromFile(string $file, FileParser $parser = null): self {
    return self::fromFileTokens(
      FileTokens_Common::fromFile($file),
      $parser);
  }

  /**
   * @param string $file
   * @param \Donquixote\QuickAttributes\Parser\FileParser|null $parser
   *
   * @return self|null
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public static function fromUnknownFile(string $file, FileParser $parser = null): ?self {
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
   * @param \Donquixote\QuickAttributes\Parser\FileParser|null $parser
   *
   * @return self
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public static function fromPhpSnippet(string $php, string $expectedClassShortname = null, FileParser $parser = null): self {
    return self::fromFileTokens(
      new FileTokens_Common($php, $expectedClassShortname),
      $parser);
  }

  /**
   * @param \Donquixote\QuickAttributes\FileTokens\FileTokensInterface $fileTokens
   * @param \Donquixote\QuickAttributes\Parser\FileParser|null $parser
   *
   * @return self
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public static function fromFileTokens(FileTokensInterface $fileTokens, FileParser $parser = null): self {
    $visitor = new SymbolVisitor_CollectInfo();
    $parser ??= FileParser::create();
    $it = $parser->parseFileTokens($fileTokens, $visitor);
    $lookup = new Lookup_LazyLoadDecorator($visitor, $it);
    return new self($lookup);
  }

  public function findClass(string $name): ?ClassInfo {
    return ClassInfo::create(
      $this->lookup,
      $name,
      $name);
  }

  public function findFunction(string $name): ?FunctionInfo {
    return FunctionInfo::create(
      $this->lookup,
      $name,
      $name . '()');
  }

  /**
   * @return \Iterator<int, ClassInfo>
   */
  public function readClasses(): \Iterator {
    foreach ($this->lookup->readToplevelNames() as $name) {
      if (\substr($name, -2) !== '()') {
        /** @var class-string $class */
        $class = $name;
        yield ClassInfo::createExpected($this->lookup, $class, $class);
      }
    }
  }

  /**
   * @return \Iterator<int, FunctionInfo>
   */
  public function readFunctions(): \Iterator {
    foreach ($this->lookup->readToplevelNames() as $name) {
      if (\substr($name, -2) === '()') {
        $function = \substr($name, 0, -2);
        yield FunctionInfo::createExpected(
          $this->lookup,
          $function,
          $function . '()');
      }
    }
  }

  /**
   * @return \Iterator<int, ClassInfo|FunctionInfo>
   */
  public function readElements(): \Iterator {
    foreach ($this->lookup->readToplevelNames() as $name) {
      if (\substr($name, -2) === '()') {
        $function = \substr($name, 0, -2);
        yield FunctionInfo::createExpected($this->lookup, $function, $function . '()');
      }
      else {
        /** @var class-string $class */
        $class = $name;
        yield ClassInfo::createExpected($this->lookup, $class, $class);
      }
    }
  }

}
