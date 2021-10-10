<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\RawAttribute;

use Donquixote\QuickAttributes\Util\MessageUtil;

/**
 * @template T as object
 *
 * @template-implements RawAttributeInterface<T>
 */
class RawAttribute_Eval implements RawAttributeInterface {

  /**
   * @var class-string<T>
   */
  private string $class;

  private string $argsPhp;

  /**
   * Constructor.
   *
   * @param class-string<T> $class
   * @param string $argsPhp
   */
  public function __construct(string $class, string $argsPhp) {
    $this->class = $class;
    $this->argsPhp = $argsPhp;
  }

  /**
   * @return class-string<T>
   */
  public function getName(): string {
    return $this->class;
  }

  /**
   * {@inheritdoc}
   */
  public function getArguments(): array {
    try {
      $args = $this->evalCode();
    }
    catch (\Throwable $e) {
      throw new \ReflectionException(
        vsprintf("%s in eval():\n  Message %s\n  Eval'd code:\n    %s", [
          get_class($e),
          var_export($e->getMessage(), TRUE),
          str_replace("\n", "\n    ", $this->argsPhp),
        ]),
        0,
        $e);
    }
    if (!is_array($args)) {
      throw new \ReflectionException(vsprintf("Expected an array, found %s, from eval(%s)", [
        MessageUtil::formatValue($args),
        var_export($this->argsPhp, TRUE),
      ]));
    }
    return $args;
  }

  /**
   * Evaluates the PHP expression.
   *
   * This happens in a separate method, to prevent tainting of local variables.
   *
   * @return mixed
   *
   * @throws \Throwable
   */
  private function evalCode() {
    return eval($this->argsPhp);
  }

}
