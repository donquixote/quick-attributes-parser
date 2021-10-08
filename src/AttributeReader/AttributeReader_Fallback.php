<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\AttributeReader;

use Donquixote\QuickAttributes\AttributesList\AttributesList_Fallback;
use Donquixote\QuickAttributes\AttributesList\AttributesListInterface;
use Donquixote\QuickAttributes\Exception\SyntaxException;
use Donquixote\QuickAttributes\Parser\AttributeCommentParser;
use Donquixote\QuickAttributes\Registry\SymbolInfoRegistry;
use Donquixote\QuickAttributes\Value\SymbolHandle;

class AttributeReader_Fallback implements AttributeReaderInterface {

  /**
   * @var \Donquixote\QuickAttributes\Registry\SymbolInfoRegistry
   */
  private SymbolInfoRegistry $registry;

  /**
   * @var \Donquixote\QuickAttributes\Parser\AttributeCommentParser
   */
  private AttributeCommentParser $attributeCommentParser;

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\Registry\SymbolInfoRegistry $registry
   * @param \Donquixote\QuickAttributes\Parser\AttributeCommentParser $attributeCommentParser
   */
  public function __construct(SymbolInfoRegistry $registry, AttributeCommentParser $attributeCommentParser) {
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
  public function read(SymbolHandle $symbol): ?AttributesListInterface {
    $comments = $this->registry->symbolGetAttributesComments($symbol);
    if (!$comments) {
      return NULL;
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
      catch (SyntaxException $e) {
        throw new \ReflectionException($e->getMessage(), 0, $e);
      }
    }
    if (!$rawAttributes) {
      return NULL;
    }
    return new AttributesList_Fallback($rawAttributes);
  }

}
