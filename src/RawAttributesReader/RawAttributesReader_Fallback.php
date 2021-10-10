<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\RawAttributesReader;

use Donquixote\QuickAttributes\AttributeCommentParser\AttributeCommentParser;
use Donquixote\QuickAttributes\AttributeCommentParser\AttributeCommentParserInterface;
use Donquixote\QuickAttributes\Exception\ParserException;
use Donquixote\QuickAttributes\Registry\SymbolInfoRegistry;
use Donquixote\QuickAttributes\Value\SymbolHandle;

class RawAttributesReader_Fallback implements RawAttributesReaderInterface {

  /**
   * @var \Donquixote\QuickAttributes\Registry\SymbolInfoRegistry
   */
  private SymbolInfoRegistry $registry;

  /**
   * @var \Donquixote\QuickAttributes\AttributeCommentParser\AttributeCommentParserInterface
   */
  private AttributeCommentParserInterface $attributeCommentParser;

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\Registry\SymbolInfoRegistry $registry
   * @param \Donquixote\QuickAttributes\AttributeCommentParser\AttributeCommentParserInterface $attributeCommentParser
   */
  public function __construct(SymbolInfoRegistry $registry, AttributeCommentParserInterface $attributeCommentParser) {
    $this->registry = $registry;
    $this->attributeCommentParser = $attributeCommentParser;
  }

  /**
   * @return self
   */
  public static function create(): self {
    return new self(
      SymbolInfoRegistry::create(),
      new AttributeCommentParser());
  }

  /**
   * {@inheritdoc}
   */
  public function read(SymbolHandle $symbol): array {
    try {
      $comments = $this->registry->symbolGetAttributesComments($symbol);
    }
    catch (\ReflectionException $e) {
      return [];
    }
    if (!$comments) {
      return [];
    }
    $imports = $this->registry->symbolGetImports($symbol);
    $commentParser = $this->attributeCommentParser->withContext(
      $symbol->getNamespaceName(),
      $imports,
      $symbol->getClassName());
    $rawAttributes = [];
    foreach ($comments as $comment) {
      try {
        foreach ($commentParser->parse($comment) as $rawAttribute) {
          $rawAttributes[] = $rawAttribute;
        }
      }
      catch (ParserException $e) {
        throw new \ReflectionException($e->getMessage(), 0, $e);
      }
    }
    return $rawAttributes;
  }

}
