<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @template-covariant TObject of object
 * @implements Type<class-string<TObject>>
 */
final class NamedClassStringType implements Type
{
    /**
     * @param Type<TObject> $type
     */
    public function __construct(
        private readonly Type $type,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->namedClassString($this, $this->type);
    }
}
