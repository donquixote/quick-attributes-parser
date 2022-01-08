# Quick Attributes Parser

[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fdonquixote%2Fquick-attributes-parser%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/donquixote/quick-attributes-parser/master)
[![Type Coverage](https://shepherd.dev/github/donquixote/quick-attributes-parser/coverage.svg)](https://shepherd.dev/github/donquixote/quick-attributes-parser)

Userland PHP parser specialized on attributes and use statements.

This package is meant for projects and packages that want to support PHP < 8 and still want attributes support.
The parser should be used in combination with native reflection.

## Parsed information
The parser only looks for information that is _not_ already available from native reflection:

- Use statements / imports.
- Attributes in PHP < 8.

## PHP versions.
It requires at least PHP 7.4. The idea is that most projects will be able to upgrade to PHP 7.4, but it will take more time to go to PHP 8.0+.

## Limitations
The parser does NOT reliably find errors in the code. 

The parser does NOT retrieve all the info in the file, only what is needed to get attributes and imports.

## Performance
The parser is optimized using the following techniques:

- Lazy / incremental parsing: It only goes as far into the file as needed to find the desired symbol(s). Once another symbol from the same file is requested, the iterator (generator) for that file will continue to run, until that other symbol is found.
- Skipping and ignoring: Large parts of code are ignored, because they have no relevant info. E.g. function bodies are completely skipped.
- Linear path: It rarely has to go back and parse the same piece of code twice.
- Optimized switch statements: PHP will optimize switch statements using a lookup table, but only if string and integer values are not mixed. Switch statements in this package that could encounter integer _or_ string are split into two parts.
- Low-level operations: The parser uses integer indices and array index lookups instead of object methods or `foreach ()`, to get to the next token.
- No token preprocessing: The parser operates directly on the result of `token_get_all()`, only one terminating `'#'` is appended to mark the EOF.
- No / lazy array copy or array slicing: The array of tokens remains unmodified throughout the parsing process, and no (or very few) other arrays are created.
- No complex AST: The parser iterates, yielding `true` values, and writes data to a visitor.

If you disagree with any of these optimization strategies, open an issue!

## Usage

```php
<?php

use Donquixote\QuickAttributes\RawAttribute\RawAttribute;
use Donquixote\QuickAttributes\Registry\ClassInfoFinder;
use Donquixote\QuickAttributes\Registry\FileInfoLoader;
use Donquixote\QuickAttributes\SymbolInfo\ClassInfo;
use Donquixote\QuickAttributes\SymbolInfo\FunctionInfo;

/**
 * Analyse a file, e.g. during a discovery operation.
 */
function processFile(string $file, string $exampleClass, string $exampleFunction) {
  $fileInfo = FileInfoLoader::create()->loadFile($file);

  // Find a specific class, if it is in the file.
  // This will only parse until the class head.
  $exampleClassInfo = $fileInfo->findClass($exampleClass);
  if ($exampleClassInfo !== null) {
    // Get imports for the class.
    $imports = $exampleClassInfo->getImports();
    unset($imports);
    // Find the constructor, read its attributes.
    $exampleClassConstructor = $exampleClassInfo->findMethod('__construct');
    if ($exampleClassConstructor !== null) {
      $attributes = $exampleClassConstructor->getAttributes();
      // (Unset to silence "unused variable" inspections.)
      unset($attributes);
    }
    // (Unset to avoid unused variable inspection. Normally you would use this
    // value for something).
    unset($exampleClassConstructor);
    // If we stop here, the rest of the file won't be parsed.
    return;
  }

  // Find specific function, if it is in the file.
  $exampleFunctionInfo = $fileInfo->findFunction($exampleFunction);
  unset($exampleFunctionInfo);

  // Read all elements in the file, using iterators.
  // Aborting an iterator means that the rest of the file won't be read.
  foreach ($fileInfo->readElements() as $element) {
    assert($element instanceof ClassInfo || $element instanceof FunctionInfo);
  }
  foreach ($fileInfo->readClasses() as $classInfo) {unset($classInfo);}
  foreach ($fileInfo->readFunctions() as $functionInfo) {unset($functionInfo);}
}

/**
 * Locate and analyse a class and its members.
 */
function processClass(string $class) {
  // Use the Composer autoloader to locate the class, then read it.
  // At this point, the parser will only go until the class head.
  $classInfo = ClassInfoFinder::create()->findClass($class);
  if ($classInfo === null) {
    return;
  }
  foreach ($classInfo->getAttributes() as $attribute) {..}
  foreach ($classInfo->getImports() as $alias => $qcn) {..}

  // Read class members, using iterators.
  // Aborting an iterator means that the rest of the file won't be read.
  foreach ($classInfo->readMembers() as ...) {..}  // All.
  foreach ($classInfo->readConstants() as ...) {..}
  foreach ($classInfo->readProperties() as ...) {..}
  foreach ($classInfo->readMethods() as $method) {
    foreach ($method->getAttributes() as $attribute) {..}
    foreach ($method->readParameters() as $param) {
      foreach ($param->getAttributes() as $attribute) {
        $name = $attribute->getName();
        $args = $attribute->getArguments();
        $instance = RawAttribute::createInstance($attribute);
        unset($name, $args, $instance);
      }
    }
  }
}
```

## Work in progress
What you see here is likely to change, before you see a first stable release..

Stay tuned..

## Alternatives & similar packages

Related popular packages:
- [nikic/php-parser](https://packagist.org/packages/nikic/php-parser):
  - The go-to userland php parser.
  - it always parses the complete file or string to the end.
  - it parses everything, including e.g. function bodies.
  - it is designed for completeness and maintainability before performance.
- [spiral/attributes](https://packagist.org/packages/spiral/attributes):
  - discovers annotations and attributes.
  - uses nikic/php-parser.
- [roave/better-reflection](https://packagist.org/packages/roave/better-reflection)
  - discovers everything that you would get with native reflection (`\ReflectionClass` and friends).
  - uses nikic/php-parser.
  - The version with attributes support requires PHP 8+.

Inspiration:
- [class StaticReflectionParser](https://git.drupalcode.org/project/drupal/-/blob/9.3.x/core/lib/Drupal/Component/Annotation/Doctrine/StaticReflectionParser.php):
  - Used to be part of Doctrine, and now has an extended life in Drupal 8/9.
  - Uses the `TokenParser` from `doctrine/annotations`.
  - Parses imports, class doc comment, class name and parent class name (extends), and skips elements that are not interesting.
- [donquixote/hasty-php-parser](https://packagist.org/packages/donquixote/hasty-php-parser):
  - Older parser I wrote years ago, and then decided not to use it.
  - Used in [donquixote/hasty-reflection-parser](https://packagist.org/packages/donquixote/hasty-reflection-parser).
  - I don't recommend using it now.
  - Many of the techniques from that package are now used here.
