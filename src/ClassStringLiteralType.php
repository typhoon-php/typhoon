<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TClass of class-string
 * @implements Type<TClass>
 */
final class ClassStringLiteralType implements Type
{
    /**
     * @var TClass
     */
    public readonly string $class;

    /**
     * @internal
     * @psalm-internal Typhoon\Type
     * @param TClass $class
     */
    public function __construct(
        string $class,
    ) {
        $this->class = $class;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitClassStringLiteral($this);
    }
}
