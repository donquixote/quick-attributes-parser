<?php

/**
 * @file
 */

declare(strict_types=1);

namespace Donquixote\QuickAttributes\AttributeCommentParser;

interface AttributeCommentParserInterface {

  /**
   * @param string|null $namespace
   * @param array<string, string> $imports
   * @param class-string|null $class
   *
   * @return static
   */
  public function withContext(?string $namespace, array $imports, ?string $class): self;

  /**
   * @param string $comment
   *
   * @return list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface>
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function parse(string $comment): array;

}
