<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpParserReflector;

use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\PhpParserReflector
 */
final class ParameterReflections
{
    private function __construct() {}

    public static function isPromoted(Param $node): bool
    {
        return ($node->flags & Class_::MODIFIER_PUBLIC) !== 0
            || ($node->flags & Class_::MODIFIER_PROTECTED) !== 0
            || ($node->flags & Class_::MODIFIER_PRIVATE) !== 0;
    }

    public static function isDefaultNull(Param $param): bool
    {
        return $param->default instanceof ConstFetch && $param->default->name->toCodeString() === 'null';
    }
}
