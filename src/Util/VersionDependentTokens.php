<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Util;

// Generate code for tokens.
\call_user_func(static function () {
  /* @see \Donquixote\QuickAttributes\Util\VersionDependentTokens */
  $php = \file_get_contents(__DIR__ . '/VersionDependentTokens.tpl.php');
  if (!\preg_match_all(
    '@ const (([A-Z_]+)(?:_(\d)|)) = (\d+);@',
    $php,
    $matches,
    \PREG_SET_ORDER
  )) {
    return;
  }
  $replacements = [];
  foreach ($matches as [, $fullname, $name, $version, $valueExpr]) {
    if (!\defined($name)) {
      continue;
    }
    if ($version !== '' && (int) $version !== \PHP_MAJOR_VERSION) {
      continue;
    }
    $replacements[" const $fullname = $valueExpr;"] = " const $fullname = \\$name;";
  }
  $php = \strtr($php, $replacements);
  $php = \preg_replace('@^<\?php@', '', $php);
  eval($php);
  if (!\class_exists(VersionDependentTokens::class, false)) {
    require_once __DIR__ . '/VersionDependentTokens.tpl.php';
  }
});
