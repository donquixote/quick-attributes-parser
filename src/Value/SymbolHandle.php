<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Value;

/**
 * Value object. Handle to identify a reflectable symbol without loading it.
 *
 * Only symbols that can have attributes are supported.
 */
final class SymbolHandle {

  /**
   * Regular expression to parse a handle id.
   *
   * @see fromId()
   * @see __toString()
   */
  public const REGEX = /** @lang RegExp */ '@^
(\w+(?:\\\\\w+)*)         # Initial qcn.
(?:|::(\\$?)(\w+))        # Optional class member name.
(?:|(\()(?:|\\$(\w+))\))  # Optional function parens and param.
($)                       # Force all groups to fill up.
@x';

  public const ALLOWED_REFLECTOR_CLASSES_MAP = [
    \ReflectionClass::class => TRUE,
    \ReflectionFunction::class => TRUE,
    \ReflectionMethod::class => TRUE,
    \ReflectionParameter::class => TRUE,
    \ReflectionProperty::class => TRUE,
    \ReflectionClassConstant::class => TRUE,
  ];

  /**
   * @var string
   */
  private string $reflectorClass;

  /**
   * @var array
   */
  private array $reflectorArgs;

  /**
   * Constructor.
   *
   * @param string $reflectorClass
   * @param array $reflectorArgs
   */
  public function __construct(string $reflectorClass, array $reflectorArgs) {
    assert(isset(self::ALLOWED_REFLECTOR_CLASSES_MAP[$reflectorClass]));
    $this->reflectorClass = $reflectorClass;
    $this->reflectorArgs = $reflectorArgs;
  }

  /**
   * Static factory.
   *
   * @param string $class
   *
   * @return self
   */
  public static function fromClass(string $class): self {
    return new self(\ReflectionClass::class, [$class]);
  }

  /**
   * Static factory.
   *
   * @param string $function
   *
   * @return self
   */
  public static function fromFunction(string $function): self {
    return new self(\ReflectionFunction::class, [$function]);
  }

  /**
   * Static factory.
   *
   * @param string $class
   * @param string $method
   *
   * @return self
   */
  public static function fromMethod(string $class, string $method): self {
    return new self(\ReflectionMethod::class, [$class, $method]);
  }

  /**
   * Static factory.
   *
   * @param string $class
   * @param string $property
   *
   * @return self
   */
  public static function fromClassProperty(string $class, string $property): self {
    return new self(\ReflectionProperty::class, [$class, $property]);
  }

  /**
   * Static factory.
   *
   * @param string $class
   * @param string $constant
   *
   * @return self
   */
  public static function fromClassConstant(string $class, string $constant): self {
    return new self(\ReflectionClassConstant::class, [$class, $constant]);
  }

  /**
   * Static factory.
   *
   * @param string $function
   * @param string $parameter
   *
   * @return self
   */
  public static function fromFunctionParameter(string $function, string $parameter): self {
    return new self(\ReflectionParameter::class, [$function, $parameter]);
  }

  /**
   * Static factory.
   *
   * @param string $class
   * @param string $method
   * @param string $parameter
   *
   * @return self
   */
  public static function fromMethodParameter(string $class, string $method, string $parameter): self {
    return new self(\ReflectionParameter::class, [[$class, $method], $parameter]);
  }

  /**
   * Static factory.
   *
   * @param string $id
   *   Id identifying the symbol, as returned from self::__toString().
   *
   * @return self
   *
   * @see __toString()
   */
  public static function fromId(string $id): self {
    if (!preg_match(self::REGEX, $id, $m)) {
      throw new \InvalidArgumentException('Invalid id.');
    }
    [, $qcn, $is_property, $member_name, $is_function, $param_name] = $m;

    if ($member_name === '') {
      // Class or global function.
      if ($is_function === '') {
        return new self(\ReflectionClass::class, [$qcn]);
      }

      if ($param_name === '') {
        return new self(\ReflectionFunction::class, [$qcn]);
      }

      return new self(\ReflectionParameter::class, [$qcn, $param_name]);
    }

    // Class member.
    $args = [$qcn, $member_name];

    if ($is_property) {
      return new self(\ReflectionProperty::class, $args);
    }

    if ($is_function === '') {
      return new self(\ReflectionClassConstant::class, $args);
    }

    if ($param_name === '') {
      return new self(\ReflectionMethod::class, $args);
    }

    return new self(\ReflectionParameter::class, [$args, $param_name]);
  }

  /**
   * Static factory.
   *
   * @param \Reflector $reflector
   *
   * @return static
   * @throws \ReflectionException
   */
  public static function fromReflector(\Reflector $reflector): self {
    $class = get_class($reflector);
    while (!isset(self::ALLOWED_REFLECTOR_CLASSES_MAP[$class])) {
      // Convert reflector adapters back to the native class.
      $class = get_parent_class($class);
      if ($class === FALSE) {
        throw new \ReflectionException(
          sprintf("Unsupported reflector class '%s'.",
            // Get the original class name for the error message.
            get_class($reflector)));
      }
    }
    return new self(
      $class,
      self::reflectorGetConstructorArgs($class, $reflector));
  }

  /**
   * @param string $class
   * @param \Reflector $reflector
   *
   * @return array
   *
   * @throws \ReflectionException
   */
  private static function reflectorGetConstructorArgs(string $class, \Reflector $reflector): array {
    switch ($class) {
      case \ReflectionClass::class:
      case \ReflectionFunction::class:
        /** @var \ReflectionClass|\ReflectionFunction $reflector */
        return [$reflector->getName()];

      case \ReflectionMethod::class:
      case \ReflectionProperty::class:
      case \ReflectionClassConstant::class:
        /** @var \ReflectionMethod|\ReflectionProperty|\ReflectionClassConstant $reflector */
        return [$reflector->getDeclaringClass()->getName(), $reflector->getName()];

      case \ReflectionParameter::class:
        /** @var \ReflectionParameter $reflector */
        $rf = $reflector->getDeclaringFunction();
        if ($rf instanceof \ReflectionMethod) {
          return [
            [$rf->getDeclaringClass()->getName(), $rf->getName()],
            $reflector->getName()
          ];
        }
        return [$rf->getName(), $reflector->getName()];

      default:
        throw new \ReflectionException('Unsupported reflector type.');
    }
  }

  /**
   * Gets a native reflector object.
   *
   * @return \ReflectionClass|\ReflectionFunction|\ReflectionMethod|\ReflectionParameter|\ReflectionProperty|\ReflectionClassConstant
   *   Native reflector object.
   */
  public function reflect(): \Reflector {
    return new $this->reflectorClass(...$this->reflectorArgs);
  }

  /**
   * Gets the canonical reflector class.
   *
   * @return string
   */
  public function getReflectorClass(): string {
    return $this->reflectorClass;
  }

  /**
   * @return array
   */
  public function getReflectorArgs(): array {
    return $this->reflectorArgs;
  }

  /**
   * Produces a unique string to distinguish this from other symbols.
   *
   * @return string
   *
   * @see fromId()
   */
  public function __toString() {
    $args = $this->reflectorArgs;
    switch ($this->reflectorClass) {
      case \ReflectionClass::class:
        return $args[0];

      case \ReflectionFunction::class:
        return $args[0] . '()';

      case \ReflectionMethod::class:
        return $args[0] . '::' . $args[1] . '()';

      case \ReflectionProperty::class:
        return $args[0] . '::$' . $args[1];

      case \ReflectionClassConstant::class:
        return $args[0] . '::' . $args[1];

      case \ReflectionParameter::class:
        return is_array($args[0])
          ? $args[0][0] . '::' . $args[0][1] . '($' . $args[1] . ')'
          : $args[0] . '($' . $args[1] . ')';

      default:
        throw new \RuntimeException('Unreachable case.');
    }
  }

  /**
   * Gets a handle to the top-level symbol that contains this.
   *
   * @return self
   */
  public function getTopLevel(): self {
    switch ($this->reflectorClass) {
      case \ReflectionMethod::class:
      case \ReflectionProperty::class:
      case \ReflectionClassConstant::class:
        return self::fromClass($this->reflectorArgs[0]);

      case \ReflectionParameter::class:
        return is_array($this->reflectorArgs[0])
          ? self::fromClass($this->reflectorArgs[0][0])
          : self::fromFunction($this->reflectorArgs[0]);

      case \ReflectionClass::class:
      case \ReflectionFunction::class:
        return $this;

      default:
        throw new \RuntimeException('Unreachable code.');
    }
  }

  /**
   * @return string
   *
   * @throws \ReflectionException
   */
  public function getFileName(): string {
    switch ($this->reflectorClass) {
      case \ReflectionFunction::class:
        return (new \ReflectionFunction($this->reflectorArgs[0]))->getFileName();

      case \ReflectionParameter::class:
        return is_array($this->reflectorArgs[0])
          ? (new \ReflectionClass($this->reflectorArgs[0][0]))->getFileName()
          : (new \ReflectionFunction($this->reflectorArgs[0]))->getFileName();

      case \ReflectionClass::class:
      case \ReflectionMethod::class:
      case \ReflectionProperty::class:
      case \ReflectionClassConstant::class:
        return (new \ReflectionClass($this->reflectorArgs[0]))->getFileName();

      default:
        throw new \RuntimeException('Unreachable code.');
    }
  }

}
