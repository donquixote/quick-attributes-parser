<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\File;

use Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilder_CollectAttributes;
use Donquixote\QuickAttributes\Builder\ClassBody\ClassBodyBuilder_CollectAttributes;
use Donquixote\QuickAttributes\Builder\ClassLike\ClassLikeBuilder;
use Donquixote\QuickAttributes\Builder\ClassLike\ClassLikeBuilderInterface;
use Donquixote\QuickAttributes\Builder\FunctionLike\FunctionLikeBuilder;
use Donquixote\QuickAttributes\Builder\FunctionLike\FunctionLikeBuilderInterface;
use Donquixote\QuickAttributes\Builder\Parameters\ParametersBuilder_CollectAttributes;

class FileBuilder_CollectImportsAndAttributes implements FileBuilderInterface {

  private array $importss;

  /**
   * @var array<string, list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface<object>>>
   */
  private array $attributess;

  /**
   * Constructor.
   *
   * @param array<string, string> $importss
   * @param array<string, list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface<object>>> $attributess
   */
  public function __construct(array &$importss, array &$attributess) {
    $this->importss =& $importss;
    $this->attributess =& $attributess;
  }

  /**
   * @inheritDoc
   */
  public function addClass(string $name, array $imports): ClassLikeBuilderInterface {
    $this->importss[$name] = $imports;
    /** @psalm-suppress MixedPropertyTypeCoercion */
    return new ClassLikeBuilder(
      new AttributesBuilder_CollectAttributes($this->attributess[$name]),
      new ClassBodyBuilder_CollectAttributes($this->attributess, $name));
  }

  /**
   * @inheritDoc
   */
  public function addFunction(string $name, array $imports): FunctionLikeBuilderInterface {
    $this->importss[$name . '()'] = $imports;
    /** @psalm-suppress MixedPropertyTypeCoercion */
    return new FunctionLikeBuilder(
      new AttributesBuilder_CollectAttributes($this->attributess[$name . '()']),
      new ParametersBuilder_CollectAttributes($this->attributess, $name));
  }

}
