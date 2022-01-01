<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Registry;

use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\Parser\FileParser;
use Donquixote\QuickAttributes\SymbolVisitor\SymbolVisitor_CollectRawSymbolInfo;
use Donquixote\QuickAttributes\Value\RawSymbolInfo;
use Donquixote\QuickAttributes\Value\SymbolHandle;

class SymbolInfoRegistry {

  /**
   * @var \Donquixote\QuickAttributes\Parser\FileParser
   */
  private FileParser $parser;

  /**
   * @var array<string, \Iterator<true>>
   */
  private array $runningIterators = [];

  private SymbolVisitor_CollectRawSymbolInfo $visitor;

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
    $this->visitor = new SymbolVisitor_CollectRawSymbolInfo();
  }

  /**
   * @return self
   */
  public static function create(): self {
    return new self(new FileParser());
  }

  /**
   * @param \Donquixote\QuickAttributes\Value\SymbolHandle $symbol
   *
   * @return array<string, string>
   *   Format (class, namespace): $[$alias] = $qcn.
   *   Format (function): $["function $alias"] = $qcn.
   *   Format (constant): $["const $alias"] = $qcn.
   *
   * @throws \ReflectionException
   *   Failed to load imports for this symbol.
   */
  public function symbolGetImports(SymbolHandle $symbol): array {
    $imports = $this->symbolGetInfo($symbol->getTopLevel())->getImports();
    if ($imports === null) {
      throw new \RuntimeException('Imports for a top-level symbol can never be NULL.');
    }
    return $imports;
  }

  /**
   * @param \Donquixote\QuickAttributes\Value\SymbolHandle $symbol
   *
   * @return string[]
   *
   * @throws \ReflectionException
   */
  public function symbolGetAttributesComments(SymbolHandle $symbol): array {
    return $this->symbolGetInfo($symbol)->getAttributeComments();
  }

  /**
   * @param \Donquixote\QuickAttributes\Value\SymbolHandle $symbol
   *
   * @return \Donquixote\QuickAttributes\Value\RawSymbolInfo
   *
   * @throws \ReflectionException
   */
  private function symbolGetInfo(SymbolHandle $symbol): RawSymbolInfo {
    $key = (string) $symbol;
    if (null !== $info = $this->visitor->getForKey($key)) {
      return $info;
    }
    $file = $symbol->getFileName();
    $it = $this->runningIterators[$file] ??= $this->itFile($file);
    while ($it->valid()) {
      if (null !== $info = $this->visitor->getForKey($key)) {
        return $info;
      }
      $it->next();
    }
    throw new \ReflectionException(
      \vsprintf('Failed to load info for %s.', [
        (string) $symbol,
      ]));
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
