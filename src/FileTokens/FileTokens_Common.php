<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\FileTokens;

use Donquixote\QuickAttributes\Exception\TokenizerException;
use Donquixote\QuickAttributes\Util\TokenizerUtil;

class FileTokens_Common implements FileTokensInterface {

  /**
   * PHP from a file.
   *
   * @var string
   */
  private string $php;

  private ?string $expectedClassShortname;

  /**
   * Constructor.
   *
   * @param string $php
   * @param string|null $expectedClassShortname
   */
  public function __construct(string $php, string $expectedClassShortname = null) {
    $this->php = $php;
    $this->expectedClassShortname = $expectedClassShortname;
  }

  /**
   * @param string $file
   * @param bool $isClassFile
   *
   * @return self
   */
  public static function fromFile(string $file, bool $isClassFile = true): self {
    $instance = self::fromUnknownFile($file, $isClassFile);
    if ($instance === null) {
      throw new \RuntimeException('File not found.');
    }
    return $instance;
  }

  /**
   * @param string $file
   * @param bool $isClassFile
   *
   * @return self|null
   *   The file info, or NULL if the file does not exist.
   */
  public static function fromUnknownFile(string $file, bool $isClassFile = true): ?self {
    if ($isClassFile
      && \preg_match('@([a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*)\.php$@', $file, $m)
    ) {
      $shortname = $m[1];
    }
    else {
      $shortname = null;
    }
    if (!\is_file($file)) {
      return null;
    }
    return new self(
      \file_get_contents($file),
      $shortname);
  }

  /**
   * @return string
   */
  private function getRegex(): string {
    $shortname = '[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*';
    $qcn = $shortname . '(?:\\\\' . $shortname . ')*';
    $name = '\\\\?' . $qcn;
    $names = $name . '(?:\s*,\s*' . $name . ')*';
    return \strtr('@
# Non-word char to isolate the subsequent keyword.
\W
(?:abstract\s+class|final\s+class|class|interface|trait)
# Whitespace before the class shortname.
\s+
# The class shortname.
%shortname
(?:|\s+extends\s+%names)
(?:|\s+implements\s+%names)
# Capture offset at opening curly bracket of class body.
\s*(\{)
@sx', [
      '%shortname' => $this->expectedClassShortname ?? $shortname,
      '%names' => $names,
    ]);
  }

  /**
   * @inheritDoc
   */
  public function getClassFileHead(): ?array {
    for ($pos = 0; null !== $pos = $this->findCandidateOpenCurlyPosition($pos);) {
      \assert($this->php[$pos] === '{');
      // Include the open curly bracket in the file head, to act as a marker.
      $php = \substr($this->php, 0, $pos + 1);
      try {
        $tokens = TokenizerUtil::tokenGetAll($php);
        if (\end($tokens) === '{') {
          // Append terminating symbol.
          $tokens[] = '#';
          return $tokens;
        }
      }
      catch (TokenizerException $e) {
        // The regex might have found an unterminated comment or string literal.
        // Try the next.
      }
    }

    // Not a regular class file, or unsupported code formatting.
    return null;
  }

  /**
   * Finds the next position that could be the beginning of the class body.
   *
   * @param int $offset
   *
   * @return int|null
   *   A position, or NULL if no further candidate positions exist.
   */
  private function findCandidateOpenCurlyPosition(int $offset = 0): ?int {
    if (!\preg_match(
      $this->getRegex(),
      $this->php,
      $m,
      \PREG_OFFSET_CAPTURE,
      $offset)
    ) {
      return null;
    }

    /** @var list<array{string, int}> $m */
    return $m[1][1];
  }

  /**
   * @inheritDoc
   */
  public function getAll(): array {
    $tokens = TokenizerUtil::tokenGetAll($this->php);
    // Append terminating '#'.
    $tokens[] = '#';
    return $tokens;
  }

}
