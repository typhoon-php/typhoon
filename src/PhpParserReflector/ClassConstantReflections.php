<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpParserReflector;

use PhpParser\Node\Stmt\ClassConst;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\PhpParserReflector
 */
final class ClassConstantReflections
{
    private function __construct() {}

    /**
     * @return int-mask-of<\ReflectionClassConstant::IS_*>
     */
    public static function modifiers(ClassConst $node): int
    {
        return ($node->isPublic() ? \ReflectionClassConstant::IS_PUBLIC : 0)
            | ($node->isProtected() ? \ReflectionClassConstant::IS_PROTECTED : 0)
            | ($node->isPrivate() ? \ReflectionClassConstant::IS_PRIVATE : 0)
            | ($node->isFinal() ? \ReflectionClassConstant::IS_FINAL : 0);
    }
}
