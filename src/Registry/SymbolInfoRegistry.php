<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Registry;

use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\Parser\FileParser;
use Donquixote\QuickAttributes\Value\RawSymbolInfo;
use Donquixote\QuickAttributes\Value\SymbolHandle;

class SymbolInfoRegistryPhp7 {

  /**
   * @var \Donquixote\QuickAttributes\Parser\FileParser
   */
  private FileParser $parser;

  /**
   * @var \Donquixote\QuickAttributes\Value\RawSymbolInfo[]
   */
  private array $info = [];

  /**
   * @var \Iterator|iterable<string, SymbolHandle>
   */
  private array $runningIterators = [];

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\Parser\FileParser $parser
   */
  public function __construct(FileParser $parser) {
    if (PHP_VERSION_ID >= 80000) {
      throw new \RuntimeException('This class should only be used in PHP < 8.');
    }
    $this->parser = $parser;
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
   * @return string[]|null
   *   Format (class, namespace): $[$alias] = $qcn.
   *   Format (function): $["function $alias"] = $qcn.
   *   Format (constant): $["const $alias"] = $qcn.
   *   For non toplevel symbols this is NULL.
   *
   * @throws \ReflectionException
   *   Failed to load imports for this symbol.
   */
  public function symbolGetImports(SymbolHandle $symbol): ?array {
    return $this->symbolGetInfo($symbol->getTopLevel())->getImports();
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
    $existing = $this->info[$key] ?? NULL;
    if ($existing !== NULL) {
      return $existing;
    }
    $file = $symbol->getFileName();
    $it = $this->runningIterators[$file] ??= $this->itFile($file);
    while ($it->valid()) {
      $it->next();
      if (isset($this->info[$key])) {
        return $this->info[$key];
      }
    }
    throw new \ReflectionException('Failed to load info for symbol.');
  }

  /**
   * @param string $file
   *
   * @return \Iterator<string, SymbolHandle>
   *
   * @throws \ReflectionException
   */
  private function itFile(string $file): \Iterator {
    try {
      foreach ($this->parser->parseFile($file) as $symbol => $info) {
        $key = (string) $symbol;
        $this->info[$key] = $info;
        yield TRUE;
      }
    }
    catch (ParserException $e) {
      throw new \ReflectionException($e->getMessage(), 0, $e);
    }
  }

}
