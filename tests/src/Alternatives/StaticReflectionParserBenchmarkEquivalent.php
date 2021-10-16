<?php
// phpcs:ignoreFile
// cspell:ignore paamayim nekudotayim

/**
 * @file
 *
 * This class has a history in Doctrine and Drupal.
 *
 * The original version (to our knowledge) was found in Doctrine, as
 * `Doctrine\Common\Reflection\StaticReflectionParser`.
 *
 * When this functionality was abandoned in Doctrine, a copy of this landed in
 * Drupal 8/9, as `Drupal\Component\Annotation\Doctrine\StaticReflectionParser`.
 *
 * What you see here is a reduced version, with the only purpose being to
 * compare its performance in benchmarks.
 */

namespace Donquixote\QuickAttributes\Tests\Alternatives;

use Doctrine\Common\Annotations\TokenParser;
use function array_merge;
use function file_get_contents;
use function is_array;
use function preg_match;
use function sprintf;
use function strpos;
use function strtolower;
use function substr;
use const T_CLASS;
use const T_DOC_COMMENT;
use const T_EXTENDS;
use const T_FUNCTION;
use const T_NEW;
use const T_PAAMAYIM_NEKUDOTAYIM;
use const T_PRIVATE;
use const T_PROTECTED;
use const T_PUBLIC;
use const T_STRING;
use const T_USE;
use const T_VAR;
use const T_VARIABLE;

/**
 * Parses a file for namespaces/use/class declarations.
 */
class StaticReflectionParserBenchmarkEquivalent {

  /**
   * @param string $class
   * @param string $file
   */
  public function parseClassFile(string $class, string $file): void {

    if (FALSE !== $lastNsPos = \strrpos($class, '\\')) {
      $namespace = substr($class, 0, $lastNsPos);
      $shortname = substr($class, $lastNsPos + 1);
    }
    else {
      $namespace = '';
      $shortname = $class;
    }

    $contents = file_get_contents($file);
    $regex = sprintf(
      '/\A.*^\s*((abstract|final)\s+)?class\s+%s\s+/sm',
      $shortname);
    if (!preg_match($regex, $contents, $matches)) {
      throw new \RuntimeException('Class head not found in php.');
    }
    $contents = $matches[0];
    $tokenParser = new TokenParser($contents);
    $docComment = '';
    /** @var array{class: string, method: array<string, string>} $docComments */
    $docComments = [
      'class' => '',
      'property' => [],
      'method' => [],
    ];
    $last_token = false;
    $imports = [];
    $extendsNames = [];
    $implementsNames = [];

    while ($token = $tokenParser->next(false)) {
      /** @var string|array{int, string, int} $token */
      switch ($token[0]) {
        case T_USE:
          $imports = array_merge($imports, $tokenParser->parseUseStatement());
          break;

        case T_DOC_COMMENT:
          /** @var array{int, string, int} $token */
          $docComment = $token[1];
          break;

        case T_CLASS:
          if ($last_token === T_PAAMAYIM_NEKUDOTAYIM || $last_token === T_NEW) {
            throw new \RuntimeException('Unexpected token found before class.');
          }
          $docComments['class'] = $docComment;
          $docComment = '';
          break;

        case T_VAR:
        case T_PRIVATE:
        case T_PROTECTED:
          /** @noinspection PhpMissingBreakStatementInspection */
        case T_PUBLIC:
          /** @var string|array{int, string, int} $token */
          $token = $tokenParser->next();
          if ($token[0] === T_VARIABLE) {
            $propertyName = substr($token[1], 1);
            $docComments['property'][$propertyName] = $docComment;
            continue 2;
          }
          if ($token[0] !== T_FUNCTION) {
            // For example, it can be T_FINAL.
            continue 2;
          }

        // No break.
        case T_FUNCTION:
          // The next string after function is the name, but
          // there can be & before the function name so find the
          // string.
          /** @var string|array{int, string, int}|null $token */
          while (($token = $tokenParser->next()) && $token[0] !== T_STRING) {}
          /** @var array{int, string, int}|null $token */
          if ($token === null) {
            break;
          }
          $methodName = $token[1];
          $docComments['method'][$methodName] = $docComment;
          $docComment = '';
          break;

        case T_EXTENDS:
          $extends = $tokenParser->parseClass();
          $extendsNsPos = strpos($extends, '\\');
          $fullySpecified = false;
          if ($extendsNsPos === 0) {
            $fullySpecified = true;
          }
          else {
            if ($extendsNsPos) {
              $prefix = strtolower(substr($extends, 0, $extendsNsPos));
              $postfix = substr($extends, $extendsNsPos);
            }
            else {
              $prefix = strtolower($extends);
              $postfix = '';
            }
            foreach ($imports as $alias => $use) {
              if ($alias !== $prefix) {
                continue;
              }
              $extends = '\\' . $use . $postfix;
              $fullySpecified        = true;
            }
          }
          if (!$fullySpecified) {
            $extends = '\\' . $namespace . '\\' . $extends;
          }
          $extendsNames[] = $extends;
          break;
      }

      $last_token = is_array($token) ? $token[0] : false;
    }

    unset($docComments, $extendsNames, $implementsNames);
  }

}
