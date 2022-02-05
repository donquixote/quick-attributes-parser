<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\Parser;

use Donquixote\QuickAttributes\Builder\File\FileBuilderInterface;
use Donquixote\QuickAttributes\FileTokens\FileTokensInterface;

class FileTokenParser_Empty implements FileTokenParserInterface {

  public function parseFileTokens(FileTokensInterface $fileTokens, FileBuilderInterface $fileBuilder): \Iterator {
    yield true;
  }

}
