<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @template-covariant TClass of non-empty-string
 * @implements Type<TClass>
 */
final class ClassStringLiteralType implements Type
{
    /**
     * @param TClass $class
     */
    public function __construct(
        private readonly string $class,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->classStringLiteral($this, $this->class);
    }
}
