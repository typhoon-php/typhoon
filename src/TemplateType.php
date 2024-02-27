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
     */
    public function __construct(
        private readonly string $name,
        private readonly AtFunction|AtClass|AtMethod $declaredAt,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->template($this, $this->name, $this->declaredAt);
    }
}
