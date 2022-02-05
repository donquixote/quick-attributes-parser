<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Builder\File;

use Donquixote\QuickAttributes\Builder\Attributes\AttributesBuilder;
use Donquixote\QuickAttributes\Builder\ClassBody\ClassBodyBuilder;
use Donquixote\QuickAttributes\Builder\ClassLike\ClassLikeBuilder;
use Donquixote\QuickAttributes\Builder\ClassLike\ClassLikeBuilderInterface;
use Donquixote\QuickAttributes\Builder\FunctionLike\FunctionLikeBuilder;
use Donquixote\QuickAttributes\Builder\FunctionLike\FunctionLikeBuilderInterface;
use Donquixote\QuickAttributes\Builder\Parameters\ParametersBuilder;
use Donquixote\QuickAttributes\SymbolInfo\ClassLike\ClassInfo;
use Donquixote\QuickAttributes\SymbolInfo\ClassLike\ClassInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\File\FileInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\FunctionLike\FunctionInfo;
use Donquixote\QuickAttributes\SymbolInfo\FunctionLike\FunctionInfoInterface;

class FileBuilderBase implements FileBuilderInterface, FileInfoInterface {

  /**
   * @var array<int, ClassInfoInterface|FunctionInfoInterface>
   */
  private array $elements = [];

  /**
   * @var array<string, int>
   */
  private $classMap = [];

  /**
   * @var array<string, int>
   */
  private $functionMap = [];

  /**
   * @var \Iterator<int, true>
   */
  private \Iterator $it;

  /**
   * Constructor.
   *
   * This constructor is not "pure", it actually starts the parsing process.
   * This is why it is protected.
   *
   * @param callable(FileBuilderInterface): \Iterator<int, true> $start
   *   Callback to start the parser iterator.
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   *   Failure to start the iterator.
   */
  protected function __construct(callable $start) {
    $this->it = $start($this);
  }

  /**
   * @inheritDoc
   */
  public function addClass(string $name, array $imports): ClassLikeBuilderInterface {
    $this->classMap[$name] = \count($this->elements);
    $attributesBuilder = new AttributesBuilder();
    $classBodyBuilder = new ClassBodyBuilder($this->it);

    $this->elements[] = new ClassInfo(
      $name,
      $imports,
      $attributesBuilder,
      $classBodyBuilder);

    return new ClassLikeBuilder(
      $attributesBuilder,
      $classBodyBuilder);
  }

  /**
   * @inheritDoc
   */
  public function addFunction(string $name, array $imports): FunctionLikeBuilderInterface {
    $this->functionMap[$name] = \count($this->elements);
    $attributesBuilder = new AttributesBuilder();
    $parametersBuilder = new ParametersBuilder($this->it);

    $this->elements[] = new FunctionInfo(
      $name,
      $imports,
      $attributesBuilder,
      $parametersBuilder);

    return new FunctionLikeBuilder(
      $attributesBuilder,
      $parametersBuilder);
  }

  public function findClass(string $name): ?ClassInfoInterface {
    $element = $this->findElement($name, $this->classMap);
    \assert($element instanceof ClassInfoInterface);
    return $element;
  }

  public function findFunction(string $name): ?FunctionInfoInterface {
    $element = $this->findElement($name, $this->functionMap);
    \assert($element instanceof FunctionInfoInterface);
    return $element;
  }

  /**
   * @param string $name
   * @param array<string, int> $map
   *
   * @return ClassInfoInterface|FunctionInfoInterface|null
   */
  private function findElement(string $name, array &$map): ?object {
    if (null !== ($index = $map[$name] ?? null)) {
      return $this->elements[$index];
    }
    while ($this->it->valid()) {
      if (null !== ($index = $map[$name] ?? null)) {
        return $this->elements[$index];
      }
      $this->it->next();
    }
    return null;
  }

  /**
   * @return \Iterator<int, ClassInfoInterface>
   */
  public function readClasses(): \Iterator {
    foreach ($this->readElements() as $member) {
      if ($member instanceof ClassInfoInterface) {
        yield $member;
      }
    }
  }

  /**
   * @return \Iterator<int, FunctionInfoInterface>
   */
  public function readFunctions(): \Iterator {
    foreach ($this->readElements() as $member) {
      if ($member instanceof FunctionInfoInterface) {
        yield $member;
      }
    }
  }

  /**
   * @return \Iterator<int, ClassInfoInterface|FunctionInfoInterface>
   */
  public function readElements(): \Iterator {
    if ($this->elements === [] && !$this->it->valid()) {
      return;
    }
    $index = 0;
    while (true) {
      if (isset($this->elements[$index])) {
        yield $this->elements[$index];
        ++$index;
        continue;
      }
      if (!$this->it->valid()) {
        return;
      }
      $this->it->next();
    }
  }

}
