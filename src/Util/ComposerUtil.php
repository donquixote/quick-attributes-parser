<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Util;

use Composer\Autoload\ClassLoader;

class ComposerUtil {

  /**
   * @return string
   */
  public static function getAutoloadFile(): string {
    try {
      $rc = new \ReflectionClass(ClassLoader::class);
    }
    catch (\ReflectionException $e) {
      throw new \RuntimeException('Cannot find composer autoloader.', 0, $e);
    }
    $file = \dirname($rc->getFileName(), 2) . '/autoload.php';
    if (!\is_file($file) || !\is_readable($file)) {
      throw new \RuntimeException('Cannot find autoload file.');
    }
    return $file;
  }

  /**
   * @return \Composer\Autoload\ClassLoader
   */
  public static function getActiveClassLoader(): ClassLoader {
    $file = self::getAutoloadFile();
    /** @psalm-suppress MixedAssignment, UnresolvableInclude */
    $loader = require $file;
    if (!$loader instanceof ClassLoader) {
      if (\is_object($loader)) {
        $found = \get_class($loader) . ' object';
      }
      elseif (\is_numeric($loader)
        || \in_array($loader, [NULL, TRUE, FALSE], TRUE)
      ) {
        $found = \var_export($loader, TRUE);
      }
      else {
        $found = \gettype($loader) . ' value';
      }
      throw new \RuntimeException(\sprintf('Expected ClassLoader object, found %s.', $found));
    }
    return $loader;
  }

}
