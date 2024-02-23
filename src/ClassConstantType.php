<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @template-covariant TClassConstant
 * @implements Type<TClassConstant>
 */
final class ClassConstantType implements Type
{
    /**
     * @param non-empty-string $class
     * @param non-empty-string $name
     */
    public function __construct(
        private readonly string $class,
        private readonly string $name,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->classConstant($this, $this->class, $this->name);
    }
}
