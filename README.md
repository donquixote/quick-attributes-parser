# Quick Attributse Parser

Userland PHP parser specialized on attributes and use statements.

This package is meant for projects and packages that want to support PHP < 8 and still want attributes support.
The parser should be used in combination with native reflection.

## Parsed information
The parser only looks for information that is _not_ already available from native reflection:

- Use statements / imports.
- Attributes in PHP < 8.

## PHP versions.
It requires at least PHP 7.4. The idea is that most projects will be able to upgrade to PHP 7.4, but it will take more time to go to PHP 8.0+.

In PHP 8+ it will still work, but the attributes parser will always turn up empty. It is the user's responsibility to implement a fallback mechanism.

## Limitations
The parser does NOT reliably find errors in the code. 

## Performance
The parser is optimized using the following techniques:

- Lazy parsing: It only goes as far into the file as needed to find the desired symbol.
- Skipping and ignoring: Large parts of code are ignored, because they have no relevant info.
- Linear path: It rarely has to go back and parse the same piece of code twice.
- Optimized switch statements: PHP will optimize switch statements using a lookup table, but only if string and integer values are not mixed.  
- Micro-optimization
