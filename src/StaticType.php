<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TObject of object
 * @implements Type<TObject>
 */
final class StaticType implements Type
{
    /**
     * @var class-string<TObject>
     */
    public readonly string $declaringClass;

    /**
     * @var list<Type>
     */
    public readonly array $templateArguments;

    /**
     * @internal
     * @psalm-internal Typhoon\Type
     * @no-named-arguments
     * @param class-string<TObject> $declaringClass
     * @param list<Type> $templateArguments
     */
    public function __construct(
        string $declaringClass,
        array $templateArguments = [],
    ) {
        $this->declaringClass = $declaringClass;
        $this->templateArguments = $templateArguments;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitStatic($this);
    }
}
