<?php

declare(strict_types=1);

if (PHP_VERSION_ID < 80000) {
  /** @psalm-suppress MissingFile */
  require_once __DIR__ . '/VersionDependentTokens.php7.php';
}
else {
  /** @psalm-suppress MissingFile */
  require_once __DIR__ . '/VersionDependentTokens.php8.php';
}
