<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @implements Type<mixed>
 */
final class ClassConstantType implements Type
{
    /**
     * @param non-empty-string $name
     */
    public function __construct(
        private readonly Type $class,
        private readonly string $name,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->classConstant($this, $this->class, $this->name);
    }
}
