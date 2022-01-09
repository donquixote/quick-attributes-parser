<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\FileTokens;

/**
 * Encapsulates the tokens within a file.
 *
 * @psalm-type _Token=string|array{int,string,int}
 * @psalm-type _TokenList=list<_Token>
 */
interface FileTokensInterface {

  /**
   * @return _TokenList|null
   *   Token list, terminated with '#', until opening '{' of class, OR
   *   NULL, if not a class file, or unexpected format.
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   *   Invalid or unsupported PHP found, failed to tokenize.
   */
  public function getClassFileHead(): ?array;

  /**
   * @return _TokenList
   *   Token list, terminated with '#', containing all tokens in the file.
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   *   Invalid or unsupported PHP found, failed to tokenize.
   */
  public function getAll(): array;

}
