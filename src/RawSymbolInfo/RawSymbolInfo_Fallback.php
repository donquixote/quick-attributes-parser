<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\RawSymbolInfo;

use Donquixote\QuickAttributes\AttributeCommentParser\AttributeCommentParserInterface;
use Donquixote\QuickAttributes\Exception\ParserException;

class RawSymbolInfo_Fallback implements RawSymbolInfoInterface {

  private AttributeCommentParserInterface $attributeCommentParser;

  /**
   * @var string[]
   */
  private array $attrComments;

  /**
   * Constructor.
   *
   * @param AttributeCommentParserInterface $attributeCommentParser
   *   Attribute comment parser, already aware of namespace, imports and class.
   * @param string[] $attrComments
   *   Attribute-like comments found by token_get_all() in PHP 7.
   */
  public function __construct(AttributeCommentParserInterface $attributeCommentParser, array $attrComments) {
    $this->attributeCommentParser = $attributeCommentParser;
    $this->attrComments = $attrComments;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributes(): array {
    $attributes = [];
    foreach ($this->attrComments as $comment) {
      try {
        foreach ($this->attributeCommentParser->parse($comment) as $attribute) {
          $attributes[] = $attribute;
        }
      }
      catch (ParserException $e) {
        throw new \ReflectionException($e->getMessage(), 0, $e);
      }
    }
    return $attributes;
  }

}
