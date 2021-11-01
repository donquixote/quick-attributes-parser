# Quick Attributes Parser


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

In PHP 8+ it will still work, but the attributes parser will always turn up empty. It is the user's responsibility to implement a fallback mechanism. (TBD)

## Limitations
The parser does NOT reliably find errors in the code. 

The parser does NOT retrieve all the info in the file, only what is needed to get attributes and imports.

## Performance
The parser is optimized using the following techniques:

- Lazy parsing: It only goes as far into the file as needed to find the desired symbol(s). Once another symbol from the same file is requested, the iterator (generator) for that file will continue to run, until that other symbol is found.
- Skipping and ignoring: Large parts of code are ignored, because they have no relevant info. E.g. function bodies are completely skipped.
- Linear path: It rarely has to go back and parse the same piece of code twice.
- Optimized switch statements: PHP will optimize switch statements using a lookup table, but only if string and integer values are not mixed. Switch statements in this package that could encounter integer _or_ string are split into two parts.
- Low-level operations: The parser uses integer indices and array index lookups instead of object methods or `foreach ()`, to get to the next token.
- No token preprocessing: The parser operates directly on the result of `token_get_all()`, only one terminating `'#'` is appended to mark the EOF.
- No / lazy array copy or array slicing: The array of tokens remains unmodified throughout the parsing process, and no (or very few) other arrays are created.
- No complex AST: The parser returns (iterates over) a list of "facts" about the world, that do not form a hierarchy. Currently these are represented as `RawSymbolInfo` objects, but this might change.

If you disagree with any of these optimization strategies, open an issue!

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
