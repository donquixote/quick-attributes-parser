<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\FileTokens;

/**
 * Encapsulates the tokens within a file.
 */
interface FileTokensInterface {

  /**
   * @return \Iterator<list<string|array{int, string, int}>>
   *   For a class file:
   *     1. All tokens until (including) the class header.
   *     2. All tokens in the file.
   *   For any other file:
   *     1. All tokens in the file.
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   *   Invalid or unsupported PHP found, failed to tokenize.
   */
  public function getTokenss(): \Iterator;

}
