<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @template-covariant TType
 * @implements Type<TType>
 */
final class TemplateType implements Type
{
    /**
     * @param non-empty-string $name
     * @param Type<TType> $constraint
     */
    public function __construct(
        private readonly string $name,
        private readonly AtFunction|AtClass|AtMethod $declaredAt,
        private readonly Type $constraint,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->template($this, $this->name, $this->declaredAt, $this->constraint);
    }
}
