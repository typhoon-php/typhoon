<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpParserReflector;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Enum_;
use Typhoon\Reflection\ClassReflection;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\PhpParserReflector
 */
final class ClassReflections
{
    private function __construct() {}

    /**
     * @return int-mask-of<\ReflectionClass::IS_*>
     */
    public static function modifiers(ClassLike $node): int
    {
        if ($node instanceof Enum_) {
            return ClassReflection::IS_FINAL;
        }

        if (!$node instanceof Class_) {
            return 0;
        }

        $modifiers = ($node->isAbstract() ? ClassReflection::IS_EXPLICIT_ABSTRACT : 0)
            | ($node->isFinal() ? ClassReflection::IS_FINAL : 0);

        if (\PHP_VERSION_ID >= 80200 && $node->isReadonly()) {
            /**
             * @var int-mask-of<\ReflectionClass::IS_*>
             * @psalm-suppress MixedOperand, UndefinedConstant, UnusedPsalmSuppress
             */
            $modifiers |= \ReflectionClass::IS_READONLY;
        }

        return $modifiers;
    }
}
