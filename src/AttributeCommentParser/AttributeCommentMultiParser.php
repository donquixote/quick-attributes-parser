<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\AttributeCommentParser;

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
   * @param list<string> $attrComments
   *
   * @return list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface>
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public function parseMultiple(array $attrComments): array {
    $attributes = [];
    foreach ($attrComments as $attrComment) {
      foreach ($this->attrCommentParser->parse($attrComment) as $attribute) {
        $attributes[] = $attribute;
      }
    }
    return $attributes;
  }

}
