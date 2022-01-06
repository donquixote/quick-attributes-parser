<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Registry;

use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\Lookup\Lookup_LazyLoadDecorator;
use Donquixote\QuickAttributes\Parser\FileParser;
use Donquixote\QuickAttributes\SymbolInfo\ClassInfo;
use Donquixote\QuickAttributes\SymbolInfo\FunctionInfo;
use Donquixote\QuickAttributes\SymbolVisitor\SymbolVisitor_CollectInfo;

class SymbolInfoRegistry {

  /**
   * @var \Donquixote\QuickAttributes\Parser\FileParser
   */
  private FileParser $parser;

  /**
   * @var array<string, \Iterator<true>>
   */
  private array $runningIterators = [];

  private SymbolVisitor_CollectInfo $visitor;

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\Parser\FileParser $parser
   */
  public function __construct(FileParser $parser) {
    if (\PHP_VERSION_ID >= 80000) {
      throw new \RuntimeException('This class should only be used in PHP < 8.');
    }
    $this->parser = $parser;
    $this->visitor = new SymbolVisitor_CollectInfo();
  }

  /**
   * @return self
   */
  public static function create(): self {
    return new self(FileParser::create());
  }

  /**
   * @param class-string $class
   *
   * @return \Donquixote\QuickAttributes\SymbolInfo\ClassInfo|null
   *
   * @throws \ReflectionException
   *   Class does not exist.
   */
  public function classGetInfo(string $class): ?ClassInfo {
    // @todo Buffer the instance and the lookup.
    $rc = new \ReflectionClass($class);
    $file = $rc->getFileName();
    $it = $this->runningIterators[$file] ??= $this->itFile($file);
    $lookup = new Lookup_LazyLoadDecorator($this->visitor, $it);
    return ClassInfo::create(
      $lookup,
      $class,
      $class);
  }

  /**
   * @param callable-string $function
   *
   * @return \Donquixote\QuickAttributes\SymbolInfo\FunctionInfo|null
   *
   * @throws \ReflectionException
   *   Function does not exist.
   */
  public function functionGetInfo(string $function): ?FunctionInfo {
    // @todo Buffer the instance and the lookup.
    $rf = new \ReflectionFunction($function);
    $file = $rf->getFileName();
    $it = $this->runningIterators[$file] ??= $this->itFile($file);
    $lookup = new Lookup_LazyLoadDecorator($this->visitor, $it);
    $imports = $lookup->keyGetImports($function . '()');
    if ($imports === null) {
      throw new \ReflectionException('Imports for class not found.');
    }
    return FunctionInfo::create(
      $lookup,
      $function,
      $function . '()');
  }

  /**
   * @param string $file
   *
   * @return \Iterator<int, true>
   *
   * @throws \ReflectionException
   */
  private function itFile(string $file): \Iterator {
    try {
      yield from $this->parser->parseFile($file, $this->visitor);
    }
    catch (ParserException $e) {
      $e->setSourceFile($file);
      throw new \ReflectionException($e->getMessage(), 0, $e);
    }
  }

}
