<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\FileTokens;

/**
 * @psalm-type _TokenList=list<string|array{int, string, int}>
 */
class FileTokens_PreComputed implements FileTokensInterface {

  /**
   * @var _TokenList|null
   */
  private ?array $tokensHead;

  /**
   * @var _TokenList
   */
  private array $tokensAll;

  /**
   * @param string $file
   *
   * @return self
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public static function fromFile(string $file): self {
    return self::fromFileTokens(
      FileTokens_Common::fromFile($file));
  }

  /**
   * @param \Donquixote\QuickAttributes\FileTokens\FileTokensInterface $fileTokens
   *
   * @return \Donquixote\QuickAttributes\FileTokens\FileTokens_PreComputed
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public static function fromFileTokens(FileTokensInterface $fileTokens): self {
    $it = $fileTokens->getTokenss();
    $tokens = $it->current();
    $it->next();
    if ($it->valid()) {
      return new self($it->current(), $tokens);
    }

    return new self($tokens, null);
  }

  /**
   * Constructor.
   *
   * @param _TokenList $tokensAll
   * @param _TokenList|null $tokensHead
   */
  public function __construct(array $tokensAll, ?array $tokensHead) {
    if ($tokensHead !== NULL) {
      \assert(\array_slice($tokensAll, 0, \count($tokensHead)) === $tokensHead);
    }
    $this->tokensHead = $tokensHead;
    $this->tokensAll = $tokensAll;
  }

  /**
   * {@inheritdoc}
   */
  public function getTokenss(): \Iterator {
    if ($this->tokensHead !== null) {
      yield $this->tokensHead;
    }
    yield $this->tokensAll;
  }

}
