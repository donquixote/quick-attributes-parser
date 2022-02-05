<?php

/**
 * @file
 */

declare(strict_types=1);

namespace Donquixote\QuickAttributes\AttributeCommentParser;

use Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilderInterface;

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
   * @param \Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilderInterface $builder
   * @param string $comment
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function parse(AttributesBuilderInterface $builder, string $comment): void;

}
