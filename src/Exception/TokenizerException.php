<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Exception;

class TokenizerException extends ParserException {

  public static function fromParseError(\ParseError $e): self {
    // @todo Enhance the exception message.
    return new self($e->getMessage(), 0, $e);
  }

  /**
   * @psalm-param array{
   *   type: int,
   *   message: string,
   *   file: string,
   *   line: int,
   * } $err
   *
   * @see \error_get_last()
   */
  public static function fromError(array $err): self {
    // @todo Enhance the exception message.
    return new self($err['message']);
  }

}
