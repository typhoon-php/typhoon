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
     * @var list<Type>
     */
    public readonly array $templateArguments;

    /**
     * @internal
     * @psalm-internal Typhoon\Type
     * @no-named-arguments
     * @param list<Type> $templateArguments
     */
    public function __construct(
        array $templateArguments = [],
    ) {
        $this->templateArguments = $templateArguments;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitStatic($this);
    }
}
