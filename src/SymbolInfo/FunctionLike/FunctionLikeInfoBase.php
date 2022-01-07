<?php

declare(strict_types=1);

namespace Donquixote\QuickAttributes\SymbolInfo\FunctionLike;

use Donquixote\QuickAttributes\SymbolInfo\Shared\SymbolInfoBase;
use Donquixote\QuickAttributes\SymbolVisitor\FunctionLike\ParamVisitorAndInfoTrait;

abstract class FunctionLikeInfoBase extends SymbolInfoBase implements FunctionLikeInfoInterface {

  use ParamVisitorAndInfoTrait;

}
