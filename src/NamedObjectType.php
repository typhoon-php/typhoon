<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @template-covariant TObject of object
 * @implements Type<TObject>
 */
final class NamedObjectType implements Type
{
    /**
     * @param class-string<TObject>|non-empty-string $class
     * @param list<Type> $templateArguments
     */
    public function __construct(
        private readonly string $class,
        private readonly array $templateArguments,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->namedObject($this, $this->class, $this->templateArguments);
    }
}
