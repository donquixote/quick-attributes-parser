<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo;

use Donquixote\QuickAttributes\FileTokens\FileTokens_Common;
use Donquixote\QuickAttributes\FileTokens\FileTokensInterface;
use Donquixote\QuickAttributes\Lookup\Lookup_LazyLoadDecorator;
use Donquixote\QuickAttributes\Lookup\LookupInterface;
use Donquixote\QuickAttributes\Parser\FileParser;
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
   * @param \Donquixote\QuickAttributes\Parser\FileParser $parser
   *
   * @return self
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public static function fromFile(string $file, FileParser $parser): self {
    return self::fromFileTokens(
      FileTokens_Common::fromFile($file),
      $parser);
  }

  /**
   * @param \Donquixote\QuickAttributes\FileTokens\FileTokensInterface $fileTokens
   * @param \Donquixote\QuickAttributes\Parser\FileParser $parser
   *
   * @return self
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public static function fromFileTokens(FileTokensInterface $fileTokens, FileParser $parser): self {
    $visitor = new SymbolVisitor_CollectInfo();
    $it = $parser->parseFileTokens($fileTokens, $visitor);
    $lookup = new Lookup_LazyLoadDecorator($visitor, $it);
    return new self($lookup);
  }

  public function findClass(string $class): ?ClassInfo {
    return ClassInfo::create(
      $this->lookup,
      $class,
      $class);
  }

  public function findFunction(string $function): ?FunctionInfo {
    return FunctionInfo::create(
      $this->lookup,
      $function,
      $function . '()');
  }

  /**
   * @param int $offset
   *
   * @return \Iterator<int, ClassInfo>
   */
  public function readClasses(int &$offset = 0): \Iterator {
    foreach ($this->lookup->readToplevelNames($offset) as $name) {
      if (\substr($name, -2) !== '()') {
        /** @var class-string $class */
        $class = $name;
        yield ClassInfo::createExpected($this->lookup, $class, $class);
      }
    }
  }

  /**
   * @param int $offset
   *
   * @return \Iterator<int, FunctionInfo>
   */
  public function readFunctions(int &$offset = 0): \Iterator {
    foreach ($this->lookup->readToplevelNames($offset) as $name) {
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
   * @param int $offset
   *
   * @return \Iterator<int, ClassInfo|FunctionInfo>
   */
  public function readElements(int &$offset = 0): \Iterator {
    foreach ($this->lookup->readToplevelNames($offset) as $name) {
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
