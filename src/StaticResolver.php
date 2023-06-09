<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeResolver;
use ExtendedTypeSystem\types;

/**
 * @api
 * @psalm-immutable
 */
final class StaticResolver extends TypeResolver
{
    /**
     * @param class-string $class
     */
    public function __construct(
        private readonly string $class,
    ) {
    }

    public function visitStatic(Type\StaticType $type): mixed
    {
        /** @psalm-suppress ImpureFunctionCall */
        $templateArguments = array_map(
            fn (Type $type): Type => $type->accept($this),
            $type->templateArguments,
        );

        return types::object($this->class, ...$templateArguments);
    }
}
