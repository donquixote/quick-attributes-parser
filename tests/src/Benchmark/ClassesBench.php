<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests\Benchmark;

use Donquixote\QuickAttributes\FileTokens\FileTokens_Common;
use Donquixote\QuickAttributes\Parser\FileParser;
use Donquixote\QuickAttributes\RawAttributesReader\RawAttributesReader;
use Donquixote\QuickAttributes\Registry\SymbolInfoRegistry;
use Donquixote\QuickAttributes\Tests\Alternatives\StaticReflectionParserBenchmarkEquivalent;
use Donquixote\QuickAttributes\Tests\Fixture\CMinimal;
use Donquixote\QuickAttributes\Value\SymbolHandle;
use PhpBench\Benchmark\Metadata\Annotations\Groups;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\OutputMode;
use PhpBench\Benchmark\Metadata\Annotations\OutputTimeUnit;
use PhpBench\Benchmark\Metadata\Annotations\ParamProviders;
use PhpBench\Benchmark\Metadata\Annotations\RetryThreshold;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use PhpBench\Benchmark\Metadata\Annotations\Warmup;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

/**
 * @Warmup(1)
 * Use warmup to preload all the parser classes.
 * This simulates a real-world scenario where many files are parsed, and the
 * parser classes only need to be loaded once at the beginning.
 * @OutputMode("throughput")
 * @OutputTimeUnit("seconds")
 * @RetryThreshold(7.5)
 */
class ClassesBench {

  /**
   * @Revs(1000)
   * @Iterations(5)
   * @Groups("init")
   * @ParamProviders("provideClasses")
   *
   * @param array{class-string} $args
   *
   * @throws \ReflectionException
   */
  public function benchNativeReflectionClass(array $args): void {
    $class = $args[0];
    new \ReflectionClass($class);
  }

  /**
   * @Revs(1000)
   * @Iterations(20)
   * @Groups("init")
   * @Warmup(1)
   */
  public function benchInitParser(): void {
    if (PHP_VERSION_ID > 80000) {
      return;
    }
    new FileParser();
  }

  /**
   * @Revs(100)
   * @Iterations(5)
   * @Groups("init")
   * @ParamProviders("provideClasses")
   *
   * @param array{class-string} $args
   *
   * @throws \ReflectionException
   */
  public function benchFileGetContents(array $args): void {
    $class = $args[0];
    $rc = new \ReflectionClass($class);
    $file = $rc->getFileName();
    $php = \file_get_contents($file);
    unset($php);
  }

  /**
   * @Revs(200)
   * @Iterations(5)
   * @Groups("full", "x")
   * @ParamProviders("providerBenchTokenGetAll")
   *
   * @param array{class-string, int} $args
   *
   * @throws \ReflectionException
   */
  public function benchTokenGetAll(array $args): void {
    [$class, $flags] = $args;
    $rc = new \ReflectionClass($class);
    $file = $rc->getFileName();
    $php = \file_get_contents($file);
    $tokens = \token_get_all($php, $flags);
    $tokens[] = '#';
    unset($tokens);
  }

  /**
   * @return \Iterator<string, array{class-string, int}>
   */
  public function providerBenchTokenGetAll(): \Iterator {
    foreach ($this->provideClasses() as [$class]) {
      // Compare with and without TOKEN_PARSE.
      yield "0:$class" => [$class, 0];
      yield "1:$class" => [$class, \TOKEN_PARSE];
    }
  }

  /**
   * @Revs(500)
   * @Iterations(10)
   * @Groups("init")
   * @ParamProviders("provideClasses")
   *
   * @param array{class-string} $args
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   * @throws \ReflectionException
   */
  public function benchParseClassFileStart(array $args): void {
    if (PHP_VERSION_ID > 80000) {
      return;
    }
    $class = $args[0];
    $rc = new \ReflectionClass($class);
    $file = $rc->getFileName();
    $parser = new FileParser();
    $parser->parseFile($file);
  }

  /**
   * @Revs(10)
   * @Iterations(10)
   * @Groups("head")
   * @ParamProviders("provideClasses")
   *
   * @param array{class-string} $args
   *
   * @throws \ReflectionException
   */
  public function benchStaticReflectionParseHead(array $args): void {
    $class = $args[0];
    $rc = new \ReflectionClass($class);
    $file = $rc->getFileName();
    $parser = new StaticReflectionParserBenchmarkEquivalent();
    $parser->parseClassFile($class, $file);
  }

  /**
   * @Revs(10)
   * @Iterations(10)
   * @Groups("head")
   * @ParamProviders("provideClasses")
   *
   * @param array{class-string} $args
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   * @throws \ReflectionException
   */
  public function benchParseClassHead(array $args): void {
    if (PHP_VERSION_ID > 80000) {
      return;
    }
    $class = $args[0];
    $rc = new \ReflectionClass($class);
    $file = $rc->getFileName();
    $parser = new FileParser();
    /**
     * @var \Donquixote\QuickAttributes\Value\SymbolHandle $symbol
     */
    foreach ($parser->parseFile($file) as $symbol => $_) {
      if ($symbol->getReflectorClass() === \ReflectionClass::class) {
        // Found!
        break;
      }
      throw new \RuntimeException('Unexpected non-class symbol above class.');
    }
  }

  /**
   * @Revs(10)
   * @Iterations(5)
   * @ParamProviders("provideClasses")
   * @Groups("head")
   *
   * @param array{class-string} $args
   *
   * @throws \ReflectionException
   */
  public function benchRegistryClass(array $args): void {
    if (PHP_VERSION_ID > 80000) {
      return;
    }
    $class = $args[0];
    $symbol = SymbolHandle::fromClass($class);
    $registry = SymbolInfoRegistry::create();
    $registry->symbolGetImports($symbol->getTopLevel());
    $registry->symbolGetAttributesComments($symbol);
  }

  /**
   * @Revs(10)
   * @Iterations(5)
   * @ParamProviders("provideClasses")
   * @Groups("head")
   *
   * @param array{class-string} $args
   *
   * @throws \ReflectionException
   */
  public function benchRawReaderClass(array $args): void {
    if (PHP_VERSION_ID > 80000) {
      return;
    }
    $class = $args[0];
    $reader = RawAttributesReader::create();
    $symbol = SymbolHandle::fromClass($class);
    $reader->read($symbol);
  }

  /**
   * @Revs(10)
   * @Iterations(5)
   * @Groups("full")
   * @ParamProviders("provideClasses")
   *
   * @param array{class-string} $args
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   * @throws \ReflectionException
   */
  public function benchParseClassFull(array $args): void {
    if (PHP_VERSION_ID > 80000) {
      return;
    }
    $class = $args[0];
    $rc = new \ReflectionClass($class);
    $file = $rc->getFileName();
    $parser = new FileParser();
    foreach ($parser->parseFile($file) as $_) {
      unset($_);
    }
  }

  /**
   * @Revs(3)
   * @Iterations(5)
   * @ParamProviders("provideClasses")
   * @Groups("full")
   *
   * @param array{class-string} $args
   *
   * @throws \ReflectionException
   */
  public function benchRegistryMember(array $args): void {
    if (PHP_VERSION_ID > 80000) {
      return;
    }
    $class = $args[0];
    $rc = new \ReflectionClass($class);
    $registry = SymbolInfoRegistry::create();
    $rm = $rc->getMethods()[0]
      ?? $rc->getProperties()[0]
      ?? $rc->getReflectionConstants()[0]
      ?? NULL;
    if (!$rm) {
      return;
    }
    $symbol = SymbolHandle::fromReflector($rm);
    $registry->symbolGetImports($symbol->getTopLevel());
    $registry->symbolGetAttributesComments($symbol);
  }

  /**
   * @Revs(3)
   * @Iterations(5)
   * @ParamProviders("provideClasses")
   * @Groups("full")
   *
   * @param array{class-string} $args
   *
   * @throws \ReflectionException
   */
  public function benchRawReaderMember(array $args): void {
    if (PHP_VERSION_ID > 80000) {
      return;
    }
    $class = $args[0];
    $rc = new \ReflectionClass($class);
    $reader = RawAttributesReader::create();
    $rm = $rc->getMethods()[0]
      ?? $rc->getProperties()[0]
      ?? $rc->getReflectionConstants()[0]
      ?? NULL;
    if (!$rm) {
      return;
    }
    $symbol = SymbolHandle::fromReflector($rm);
    $reader->read($symbol);
  }

  /**
   * @Revs(3)
   * @Iterations(5)
   * @Groups("full")
   * @ParamProviders("provideClasses")
   *
   * @param array{class-string} $args
   *
   * @throws \ReflectionException
   */
  public function benchParseClassNikicPhpParser(array $args): void {
    $class = $args[0];
    $rc = new \ReflectionClass($class);
    $file = $rc->getFileName();
    $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
    $php = \file_get_contents($file);
    $ast = $parser->parse($php);
    unset($ast);
  }

  /**
   * @return \Iterator<class-string, array{class-string}>
   */
  public function provideClasses(): \Iterator {
    foreach ($this->itClasses() as $class) {
      yield $class => [$class];
    }
  }

  /**
   * @return \Iterator<int, class-string>
   */
  protected function itClasses(): \Iterator {
    yield self::class;
    yield CMinimal::class;
    yield TestCase::class;
  }

}
