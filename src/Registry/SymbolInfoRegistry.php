<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Registry;

use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\FileTokens\FileTokens_Common;
use Donquixote\QuickAttributes\Parser\FileParser;
use Donquixote\QuickAttributes\Value\RawSymbolInfo;
use Donquixote\QuickAttributes\Value\SymbolHandle;

class SymbolInfoRegistry {

  /**
   * @var \Donquixote\QuickAttributes\Parser\FileParser
   */
  private FileParser $parser;

  /**
   * @var \Donquixote\QuickAttributes\Value\RawSymbolInfo[]
   */
  private array $info = [];

  /**
   * @var \Iterator<int, true>[]
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
    if ($imports === NULL) {
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
    $existing = $this->info[$key] ?? NULL;
    if ($existing !== NULL) {
      return $existing;
    }
    $file = $symbol->getFileName();
    $it = $this->runningIterators[$file] ??= $this->itFile($file);
    while ($it->valid()) {
      if (isset($this->info[$key])) {
        return $this->info[$key];
      }
      $it->next();
    }
    throw new \ReflectionException(
      vsprintf('Failed to load info for %s.', [
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
      foreach ($this->parser->parseFile($file) as $symbol => $info) {
        $key = (string) $symbol;
        $this->info[$key] = $info;
        yield TRUE;
      }
    }
    catch (ParserException $e) {
      $e->setSourceFile($file);
      throw new \ReflectionException($e->getMessage(), 0, $e);
    }
  }

}
