<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @implements Type<mixed>
 */
final class TemplateType implements Type
{
    /**
     * @param non-empty-string $name
     * @param list<Type> $arguments
     */
    public function __construct(
        private readonly string $name,
        private readonly AtFunction|AtClass|AtMethod $declaredAt,
        private readonly array $arguments,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->template($this, $this->name, $this->declaredAt, $this->arguments);
    }
}
