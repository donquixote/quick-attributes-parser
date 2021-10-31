<?php /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */

declare(strict_types=1);

namespace Donquixote\QuickAttributes\AttributesList;

class AttributesList_Native implements AttributesListInterface {

  /**
   * @var \Donquixote\QuickAttributes\Stub\SymbolReflectionInterface
   */
  private \Reflector $reflector;

  /**
   * Constructor.
   *
   * @param \Donquixote\QuickAttributes\Stub\SymbolReflectionInterface $reflector
   */
  public function __construct(\Reflector $reflector) {
    /** @psalm-suppress RedundantConditionGivenDocblockType */
    assert(method_exists($reflector, 'getAttributes'));
    $this->reflector = $reflector;
  }

  /**
   * {@inheritdoc}
   */
  public function has(string $type = NULL): bool {
    return (bool) $this->reflector->getAttributes(
      $type,
      \ReflectionAttribute::IS_INSTANCEOF);
  }

  /**
   * {@inheritdoc}
   */
  public function count(string $type = NULL): int {
    return \count(
      $this->reflector->getAttributes(
        $type,
        \ReflectionAttribute::IS_INSTANCEOF));
  }

  /**
   * {@inheritdoc}
   */
  public function createInstances(string $type = NULL): array {
    $instances = [];
    foreach ($this->reflector->getAttributes(
      $type,
      \ReflectionAttribute::IS_INSTANCEOF) as $reflectionAttribute) {
      $instances[] = $reflectionAttribute->newInstance();
    }
    return $instances;
  }

}
