<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @template-covariant TClassConstant
 * @implements Type<TClassConstant>
 */
final class ClassConstantType implements Type
{
    /**
     * @var non-empty-string
     */
    public readonly string $class;

    /**
     * @var non-empty-string
     */
    public readonly string $constant;

    /**
     * @internal
     * @psalm-internal Typhoon\Type
     * @param non-empty-string $class
     * @param non-empty-string $constant
     */
    public function __construct(
        string $class,
        string $constant,
    ) {
        $this->constant = $constant;
        $this->class = $class;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitClassConstant($this);
    }
}
