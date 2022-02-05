<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\AttributeCommentParser;

use Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilderInterface;

/**
 * Wrapper to parse multiple comments at once.
 */
class AttributeCommentMultiParser {

  /**
   * @var \Donquixote\QuickAttributes\AttributeCommentParser\AttributeCommentParserInterface
   */
  private AttributeCommentParserInterface $attrCommentParser;

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\AttributeCommentParser\AttributeCommentParserInterface $attrCommentParser
   */
  public function __construct(AttributeCommentParserInterface $attrCommentParser) {
    $this->attrCommentParser = $attrCommentParser;
  }

  /**
   * @param string|null $namespace
   * @param array<string, string> $imports
   * @param class-string|null $class
   *
   * @return static
   */
  public function withContext(?string $namespace, array $imports, ?string $class): self {
    $clone = clone $this;
    $clone->attrCommentParser = $this->attrCommentParser->withContext($namespace, $imports, $class);
    return $clone;
  }

  /**
   * @param \Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilderInterface $builder
   * @param list<string> $attrComments
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function parseMultiple(AttributesBuilderInterface $builder, array $attrComments): void {
    foreach ($attrComments as $attrComment) {
      $this->attrCommentParser->parse($builder, $attrComment);
    }
  }

}
