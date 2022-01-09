<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\ClassLike;

use Donquixote\QuickAttributes\Loader\ClassInfoFinder;
use Donquixote\QuickAttributes\SymbolInfo\Shared\GlobalSymbolInfoBase;
use Donquixote\QuickAttributes\SymbolVisitor\ClassLike\ClassMemberVisitorAndInfoTrait;
use Donquixote\QuickAttributes\SymbolVisitor\ClassLike\ClassMemberVisitorInterface;

class ClassInfo extends GlobalSymbolInfoBase implements ClassInfoInterface, ClassMemberVisitorInterface {

  use ClassMemberVisitorAndInfoTrait;

  /**
   * @var class-string
   */
  private string $name;

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
  public static function fromExpectedClass(string $class): ClassInfoInterface {
    return ClassInfoFinder::create()->requireClass($class);
  }

  /**
   * Constructor.
   *
   * @param class-string $name
   * @param array<string, string> $imports
   * @param list<\Donquixote\QuickAttributes\RawAttribute\RawAttributeInterface> $attributes
   * @param \Iterator<int, true> $it
   */
  public function __construct(string $name, array $imports, array $attributes, \Iterator $it) {
    parent::__construct($imports, $attributes);
    $this->name = $name;
    $this->it = $it;
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
