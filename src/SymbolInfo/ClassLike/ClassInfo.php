<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\ClassLike;

use Donquixote\QuickAttributes\Loader\ClassInfoFinder;
use Donquixote\QuickAttributes\SymbolInfo\Shared\AttributesInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\Shared\GlobalSymbolInfoBase;

class ClassInfo extends GlobalSymbolInfoBase implements ClassInfoInterface {

  use ClassBodyInfoDecoratorTrait;

  /**
   * @var class-string
   */
  private string $name;

  /**
   * Constructor.
   *
   * @param class-string $name
   * @param array<string, string> $imports
   * @param \Donquixote\QuickAttributes\SymbolInfo\Shared\AttributesInfoInterface $attributes
   * @param \Donquixote\QuickAttributes\SymbolInfo\ClassLike\ClassBodyInfoInterface $body
   */
  public function __construct(string $name, array $imports, AttributesInfoInterface $attributes, ClassBodyInfoInterface $body) {
    parent::__construct($imports, $attributes);
    $this->name = $name;
    $this->body = $body;
  }

  /**
   * @param class-string $class
   *
   * @return \Donquixote\QuickAttributes\SymbolInfo\ClassLike\ClassInfoInterface|null
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public static function fromClass(string $class): ?ClassInfoInterface {
    return ClassInfoFinder::create()->findClass($class);
  }

  /**
   * @param class-string $class
   *
   * @return \Donquixote\QuickAttributes\SymbolInfo\ClassLike\ClassInfoInterface
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  public static function fromKnownClass(string $class): ClassInfoInterface {
    return ClassInfoFinder::create()->requireClass($class);
  }

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return $this->name;
  }

  /**
   * @return class-string
   */
  public function getName(): string {
    return $this->name;
  }

}
