<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeVisitor;

/**
 * @psalm-api
 * @psalm-immutable
 * @template-covariant TObject of object
 * @implements Type<TObject>
 */
final class StaticT implements Type
{
    /**
     * @var list<Type>
     */
    public readonly array $templateArguments;

    /**
     * @no-named-arguments
     * @param class-string<TObject> $declaringClass
     */
    public function __construct(
        public readonly string $declaringClass,
        Type ...$templateArguments,
    ) {
        $this->templateArguments = $templateArguments;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitStatic($this);
    }
}
