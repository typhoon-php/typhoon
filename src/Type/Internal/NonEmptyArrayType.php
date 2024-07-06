<?php

declare(strict_types=1);

namespace Typhoon\Type\Internal;

use Typhoon\Type\Type;
use Typhoon\Type\types;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @psalm-immutable
 * @implements Type<non-empty-array<mixed>>
 */
final class NonEmptyArrayType implements Type
{
    public function __construct(
        private readonly Type $key,
        private readonly Type $value,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->intersection($this, [
            new NotType(new ArrayType(types::never, types::never, [])),
            new ArrayType($this->key, $this->value, []),
        ]);
    }
}
