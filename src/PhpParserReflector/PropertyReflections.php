<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpParserReflector;

use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;

final class PropertyReflections
{
    /**
     * @psalm-suppress UnusedConstructor
     */
    private function __construct() {}

    /**
     * @return int-mask-of<\ReflectionProperty::IS_*>
     */
    public static function modifiers(Property $node, bool $classReadOnly): int
    {
        return ($node->isStatic() ? \ReflectionProperty::IS_STATIC : 0)
            | ($node->isPublic() ? \ReflectionProperty::IS_PUBLIC : 0)
            | ($node->isProtected() ? \ReflectionProperty::IS_PROTECTED : 0)
            | ($node->isPrivate() ? \ReflectionProperty::IS_PRIVATE : 0)
            | ($classReadOnly || $node->isReadonly() ? \ReflectionProperty::IS_READONLY : 0);
    }

    /**
     * @return int-mask-of<\ReflectionProperty::IS_*>
     */
    public static function promotedModifiers(Param $node, bool $classReadOnly): int
    {
        return (($node->flags & Class_::MODIFIER_PUBLIC) !== 0 ? \ReflectionProperty::IS_PUBLIC : 0)
            | (($node->flags & Class_::MODIFIER_PROTECTED) !== 0 ? \ReflectionProperty::IS_PROTECTED : 0)
            | (($node->flags & Class_::MODIFIER_PRIVATE) !== 0 ? \ReflectionProperty::IS_PRIVATE : 0)
            | (($classReadOnly || ($node->flags & Class_::MODIFIER_READONLY) !== 0) ? \ReflectionProperty::IS_READONLY : 0);
    }
}
