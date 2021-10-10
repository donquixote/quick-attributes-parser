<?php /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */

declare(strict_types=1);

namespace Donquixote\QuickAttributes\RawAttribute;

/**
 * @template-covariant T as object
 *
 * @template-implements RawAttributeInterface<T>
 */
class RawAttribute_NativeReflection implements RawAttributeInterface {

  /**
   * @var \ReflectionAttribute<T>
   */
  private \ReflectionAttribute $attribute;

  /**
   * Constructor.
   *
   * @param \ReflectionAttribute<T> $attribute
   */
  public function __construct(\ReflectionAttribute $attribute) {
    if (PHP_VERSION_ID < 80000) {
      throw new \RuntimeException('This class requires PHP 8+.');
    }
    $this->attribute = $attribute;
  }

  /**
   * @return class-string<T>
   */
  public function getName(): string {
    return $this->attribute->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function getArguments(): array {
    return $this->attribute->getArguments();
  }

}
