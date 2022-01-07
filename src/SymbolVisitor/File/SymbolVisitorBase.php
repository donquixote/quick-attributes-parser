<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolVisitor\File;

use Donquixote\QuickAttributes\FileTokens\FileTokensInterface;
use Donquixote\QuickAttributes\Parser\FileTokenParserInterface;
use Donquixote\QuickAttributes\SymbolInfo\ClassLike\ClassInfo;
use Donquixote\QuickAttributes\SymbolInfo\ClassLike\ClassInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\ClassLike\FunctionInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\File\FileInfoInterface;
use Donquixote\QuickAttributes\SymbolInfo\FunctionLike\FunctionInfo;
use Donquixote\QuickAttributes\SymbolVisitor\ClassLike\ClassMemberVisitorInterface;
use Donquixote\QuickAttributes\SymbolVisitor\FunctionLike\ParamVisitorInterface;

class SymbolVisitorBase implements SymbolVisitorInterface, FileInfoInterface {

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
   * @param FileTokenParserInterface $parser
   * @param FileTokensInterface $fileTokens
   *
   * @throws \Donquixote\QuickAttributes\Exception\ParserException
   */
  protected function __construct(FileTokenParserInterface $parser, FileTokensInterface $fileTokens) {
    $this->it = $parser->parseFileTokens($fileTokens, $this);
  }

  /**
   * @inheritDoc
   */
  public function addClass(string $name, array $imports, array $attributes): ClassMemberVisitorInterface {
    $this->classMap[$name] = \count($this->elements);
    return $this->elements[] = new ClassInfo(
      $name,
      $imports,
      $attributes,
      $this->it);
  }

  /**
   * @inheritDoc
   */
  public function addFunction(string $name, array $imports, array $attributes): ParamVisitorInterface {
    $this->functionMap[$name] = \count($this->elements);
    return $this->elements[] = new FunctionInfo(
      $name,
      $attributes,
      $imports,
      $this->it);
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
