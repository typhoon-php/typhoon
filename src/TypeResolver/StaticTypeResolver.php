<?php

declare(strict_types=1);

namespace Typhoon\Reflection\TypeResolver;

use Typhoon\Type;
use Typhoon\Type\types;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-suppress UnusedClass
 */
final class StaticTypeResolver extends RecursiveTypeReplacer
{
    /**
     * @param class-string $class
     */
    public function __construct(
        private readonly string $class,
    ) {}

    public function visitStatic(Type\StaticType $type): mixed
    {
        return types::object($this->class, ...array_map(
            fn(Type\Type $templateArgument): Type\Type => $templateArgument->accept($this),
            $type->templateArguments,
        ));
    }
}
