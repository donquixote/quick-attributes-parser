<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Tests\Benchmark;

use Donquixote\QuickAttributes\FileTokens\FileTokens_Common;
use Donquixote\QuickAttributes\FileTokens\FileTokens_PreComputed;
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
use PhpBench\Benchmark\Metadata\Annotations\Sleep;
use PhpBench\Benchmark\Metadata\Annotations\Warmup;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

/**
 * @Warmup(2)
 * Use warmup to preload all the parser classes.
 * This simulates a real-world scenario where many files are parsed, and the
 * parser classes only need to be loaded once at the beginning.
 * @OutputMode("throughput")
 * @OutputTimeUnit("seconds")
 * @RetryThreshold(3.5)
 *
 * @psalm-type _TokenList=list<string|array{int,string,int}>
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
   */
  public function benchInitParser(): void {
    if (\PHP_VERSION_ID > 80000) {
      return;
    }
    new FileParser();
  }

  /**
   * @Revs(100)
   * @Iterations(5)
   * @Groups("init")
   * @ParamProviders("provideClassFiles")
   *
   * @param array{string} $args
   */
  public function benchFileGetContents(array $args): void {
    $file = $args[0];
    $php = \file_get_contents($file);
    unset($php);
  }

  /**
   * @Revs(1)
   * @Iterations(15)
   * @Groups("tokenize", "tokenize-twice")
   * @ParamProviders("provideClassFileContents")
   *
   * @param array{string} $args
   */
  public function benchTokenGetAllTwice(array $args): void {
    [$php] = $args;
    $tokens = \token_get_all($php);
    $tokens2 = \token_get_all($php);
    unset($tokens, $tokens2);
  }

  /**
   * @Revs(1)
   * @Iterations(15)
   * @Groups("tokenize", "tokenize-twice")
   * @ParamProviders("provideClassFileContents")
   *
   * @param array{string} $args
   */
  public function benchTokenGetAllConcat(array $args): void {
    [$php] = $args;
    $tokens = \token_get_all($php . '?>' . $php);
    unset($tokens);
  }

  /**
   * @Revs(200)
   * @Iterations(15)
   * @Groups("full", "tokenize", "tokenize-full")
   * @ParamProviders("provideClassFileContents")
   *
   * @param array{string} $args
   */
  public function benchTokenGetAll(array $args): void {
    [$php] = $args;
    $tokens = \token_get_all($php);
    unset($tokens);
  }

  /**
   * @Revs(200)
   * @Iterations(15)
   * @Groups("full", "tokenize", "tokenize-full")
   * @ParamProviders("provideClassFileContents")
   *
   * @param array{string} $args
   */
  public function benchTokenGetAllTerminated(array $args): void {
    [$php] = $args;
    $tokens = \token_get_all($php);
    $tokens[] = '#';
    unset($tokens);
  }

  /**
   * @Revs(200)
   * @Iterations(15)
   * @Groups("full", "tokenize", "tokenize-full")
   * @ParamProviders("provideClassFileContents")
   *
   * @param array{string, class-string, string} $args
   */
  public function benchTokenGetAllHeadThenRest(array $args): void {
    [$php,, $shortname] = $args;
    [$phpFileHead, $phpClassHead, $phpRemaining] = \preg_split(
      '@(' . \preg_quote('class ' . $shortname, '@') . '[^\{]*\{)@',
      $php,
      2,
      \PREG_SPLIT_DELIM_CAPTURE);
    $phpHead = $phpFileHead . $phpClassHead;
    $phpRemainingExtended = '<?php  '
      // Prepend new lines to make sure line numbers match up.
      . \str_repeat(
        "\n",
        \substr_count($phpHead, "\n"))
      // Include the open curly bracket.
      . '{'
      . $phpRemaining;
    $tokensHead = \token_get_all($phpHead);
    $tokensRest = \array_slice(\token_get_all($phpRemainingExtended), 3);
    $tokensAll = [...$tokensHead, ...$tokensRest, '#'];
    unset($tokensAll);
  }

  /**
   * @Revs(200)
   * @Iterations(15)
   * @Groups("full", "tokenize", "tokenize-full")
   * @ParamProviders("provideClassFileContents")
   *
   * @param array{string, class-string, string} $args
   */
  public function benchTokenGetAllHeadThenAll(array $args): void {
    [$php,, $shortname] = $args;
    [$phpFileHead, $phpClassHead] = \preg_split(
      '@(' . \preg_quote('class ' . $shortname, '@') . '[^\{]*\{)@',
      $php,
      2,
      \PREG_SPLIT_DELIM_CAPTURE);
    $phpHead = $phpFileHead . $phpClassHead;
    $tokensHead = \token_get_all($phpHead);
    $tokensAll = \token_get_all($php);
    unset($tokensHead, $tokensAll);
  }

  /**
   * @Revs(200)
   * @Iterations(15)
   * @Groups("full", "tokenize", "tokenize-full")
   * @ParamProviders("provideClassFileContents")
   *
   * @param array{string} $args
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function benchTokenHeadThenRest(array $args): void {
    [$php] = $args;
    $fileTokens = new FileTokens_Common($php);
    $fileTokens->getClassFileHead();
    $fileTokens->getAll();
  }

  /**
   * @Revs(200)
   * @Iterations(15)
   * @Groups("full", "tokenize", "tokenize-full")
   * @ParamProviders("provideClassFileContents")
   *
   * @param array{string} $args
   */
  public function benchTokenGetAllTokenParse(array $args): void {
    [$php] = $args;
    $tokens = \token_get_all($php, \TOKEN_PARSE);
    $tokens[] = '#';
    unset($tokens);
  }

  /**
   * @Revs(200)
   * @Iterations(15)
   * @Groups("head", "tokenize")
   * @ParamProviders("provideClassFileContents")
   *
   * @param array{string} $args
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function benchTokenHeadOnly(array $args): void {
    [$php] = $args;
    $fileTokens = new FileTokens_Common($php);
    $fileTokens->getClassFileHead();
  }

  /**
   * @Revs(200)
   * @Iterations(15)
   * @Groups("helper", "tokenize")
   * @ParamProviders("provideClassFileTokensObject")
   *
   * @param array{object{tokens: _TokenList}} $args
   */
  public function benchTokensNoop(array $args): void {
    [$obj] = $args;
    unset($obj);
  }

  /**
   * @Revs(200)
   * @Iterations(15)
   * @Groups("helper", "tokenize")
   * @ParamProviders("provideClassFileTokensObject")
   *
   * @param array{object{tokens: _TokenList}} $args
   */
  public function benchTokensArrayUnshift(array $args): void {
    [$obj] = $args;
    \array_unshift($obj->tokens, '$');
    unset($obj);
  }

  /**
   * @Revs(200)
   * @Iterations(15)
   * @Groups("helper", "tokenize")
   * @ParamProviders("provideClassFileTokensObject")
   *
   * @param array{object{tokens: _TokenList}} $args
   */
  public function benchTokensArrayAppend(array $args): void {
    [$obj] = $args;
    $obj->tokens[] = '#';
    unset($obj);
  }

  /**
   * @Revs(200)
   * @Iterations(5)
   * @Groups("helper", "tokenize")
   * @ParamProviders("provideClassFileTokensObject")
   *
   * @param array{object{tokens: _TokenList}} $args
   */
  public function benchTokensSetFirst(array $args): void {
    [$obj] = $args;
    $obj->tokens[0] = '$';
    unset($obj);
  }

  /**
   * @Revs(200)
   * @Iterations(5)
   * @Groups("helper", "tokenize")
   * @ParamProviders("provideClassFileTokensObject")
   *
   * @param array{object{tokens: _TokenList}} $args
   */
  public function benchTokensSetLast(array $args): void {
    [$obj] = $args;
    $obj->tokens[\count($obj->tokens) - 1] = '$';
    unset($obj);
  }

  /**
   * @Revs(200)
   * @Iterations(15)
   * @Groups("helper", "tokenize")
   * @ParamProviders("provideClassFileTokensObject")
   *
   * @param array{object{tokens: _TokenList}} $args
   */
  public function benchTokensArrayMerge(array $args): void {
    [$obj] = $args;
    $tokensMerged = [...$obj->tokens, ...$obj->tokens, '#'];
    unset($tokensMerged);
  }

  /**
   * @Revs(500)
   * @Iterations(10)
   * @Groups("init")
   * @ParamProviders("provideClassFiles")
   *
   * @param array{string} $args
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function benchParseClassFileStart(array $args): void {
    if (\PHP_VERSION_ID > 80000) {
      return;
    }
    $file = $args[0];
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
   *
   * @see StaticReflectionParserBenchmarkEquivalent
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
   * @ParamProviders("provideClassFiles")
   *
   * @param array{string} $args
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function benchParseClassHead(array $args): void {
    if (\PHP_VERSION_ID > 80000) {
      return;
    }
    $file = $args[0];
    $parser = new FileParser();
    $symbol = $parser->parseFile($file)->key();
    \assert($symbol instanceof SymbolHandle);
    if ($symbol->getReflectorClass() !== \ReflectionClass::class) {
      throw new \RuntimeException('Unexpected non-class symbol above class.');
    }
  }

  /**
   * @Revs(10)
   * @Iterations(5)
   * @ParamProviders("provideClasses")
   * @Groups("head", "registry")
   *
   * @param array{class-string} $args
   *
   * @throws \ReflectionException
   */
  public function benchRegistryClass(array $args): void {
    if (\PHP_VERSION_ID > 80000) {
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
    if (\PHP_VERSION_ID > 80000) {
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
   * @Groups("full", "parse-full")
   * @ParamProviders("provideClassFiles")
   *
   * @param array{string} $args
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function benchParseClassFull(array $args): void {
    if (\PHP_VERSION_ID > 80000) {
      return;
    }
    $file = $args[0];
    $parser = new FileParser();
    foreach ($parser->parseFile($file) as $_) {
      unset($_);
    }
  }

  /**
   * @Revs(10)
   * @Iterations(5)
   * @Groups("full", "parse-full", "parse-tokens-full")
   * @ParamProviders("provideClassFileTokens")
   *
   * @param array{\Donquixote\QuickAttributes\FileTokens\FileTokensInterface} $args
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function benchParseTokensFull(array $args): void {
    if (\PHP_VERSION_ID > 80000) {
      return;
    }
    $fileTokens = $args[0];
    $parser = new FileParser();
    foreach ($parser->parseFileTokens($fileTokens) as $_) {
      unset($_);
    }
  }

  /**
   * @Revs(3)
   * @Iterations(5)
   * @Groups("full", "registry")
   * @ParamProviders("provideClassFiles")
   *
   * @param array{string} $args
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function benchParseClassFirstMember(array $args): void {
    if (\PHP_VERSION_ID > 80000) {
      return;
    }
    $file = $args[0];
    $parser = new FileParser();
    $it = $parser->parseFile($file);
    $it->current();
    $it->next();
    $it->current();
  }

  /**
   * @Revs(10)
   * @Iterations(5)
   * @ParamProviders("provideClasses")
   * @Groups("full", "registry")
   *
   * @param array{class-string} $args
   *
   * @throws \ReflectionException
   */
  public function benchRegistryFirstMember(array $args): void {
    if (\PHP_VERSION_ID > 80000) {
      return;
    }
    $class = $args[0];
    $rc = new \ReflectionClass($class);
    $registry = SymbolInfoRegistry::create();
    $rm = $rc->getReflectionConstants()[0]
      ?? $rc->getProperties()[0]
      ?? $rc->getMethods()[0]
      ?? null;
    if (!$rm) {
      return;
    }
    $symbol = SymbolHandle::fromReflector($rm);
    $registry->symbolGetImports($symbol->getTopLevel());
    $registry->symbolGetAttributesComments($symbol);
  }

  /**
   * @Revs(10)
   * @Iterations(5)
   * @ParamProviders("provideClasses")
   * @Groups("full", "registry")
   *
   * @param array{class-string} $args
   *
   * @throws \ReflectionException
   */
  public function benchRegistryLastMember(array $args): void {
    if (\PHP_VERSION_ID > 80000) {
      return;
    }
    $class = $args[0];
    $rc = new \ReflectionClass($class);
    $registry = SymbolInfoRegistry::create();
    $rm = \array_reverse($rc->getMethods())[0]
      ?? \array_reverse($rc->getProperties())[0]
      ?? \array_reverse($rc->getReflectionConstants())[0]
      ?? null;
    if (!$rm) {
      return;
    }
    $symbol = SymbolHandle::fromReflector($rm);
    $registry->symbolGetImports($symbol->getTopLevel());
    $registry->symbolGetAttributesComments($symbol);
  }

  /**
   * @Revs(10)
   * @Iterations(5)
   * @ParamProviders("provideClasses")
   * @Groups("full")
   *
   * @param array{class-string} $args
   *
   * @throws \ReflectionException
   */
  public function benchRawReaderFirstMember(array $args): void {
    if (\PHP_VERSION_ID > 80000) {
      return;
    }
    $class = $args[0];
    $rc = new \ReflectionClass($class);
    $reader = RawAttributesReader::create();
    $rm = $rc->getReflectionConstants()[0]
      ?? $rc->getProperties()[0]
      ?? $rc->getMethods()[0]
      ?? null;
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
   * @return \Iterator<class-string, array{object{tokens: _TokenList}}>
   *
   * @throws \ReflectionException
   */
  public function provideClassFileTokensObject(): \Iterator {
    foreach ($this->itClasses() as $class) {
      $rc = new \ReflectionClass($class);
      $file = $rc->getFileName();
      if (!$file) {
        continue;
      }
      $php = \file_get_contents($file);
      $tokens = \token_get_all($php);
      yield $class => [(object)['tokens' => $tokens]];
    }
  }

  /**
   * @return \Iterator<class-string, array{\Donquixote\QuickAttributes\FileTokens\FileTokensInterface}>
   *
   * @throws \ReflectionException
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function provideClassFileTokens(): \Iterator {
    foreach ($this->itClasses() as $class) {
      $rc = new \ReflectionClass($class);
      $file = $rc->getFileName();
      if (!$file) {
        continue;
      }
      yield $class => [FileTokens_PreComputed::fromFile($file)];
    }
  }

  /**
   * @return \Iterator<class-string, array{string, class-string, string}>
   *
   * @throws \ReflectionException
   */
  public function provideClassFileContents(): \Iterator {
    foreach ($this->itClasses() as $class) {
      $rc = new \ReflectionClass($class);
      $file = $rc->getFileName();
      if (!$file) {
        continue;
      }
      $php = \file_get_contents($file);
      yield $class => [$php, $rc->getName(), $rc->getShortName()];
    }
  }

  /**
   * @return \Iterator<class-string, array{string, class-string, string, string}>
   *
   * @throws \ReflectionException
   */
  public function provideClassFiles(): \Iterator {
    foreach ($this->itClasses() as $class) {
      $rc = new \ReflectionClass($class);
      $file = $rc->getFileName();
      if (!$file) {
        continue;
      }
      yield $class => [$file, $class, $rc->getShortName(), $rc->getNamespaceName()];
    }
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
