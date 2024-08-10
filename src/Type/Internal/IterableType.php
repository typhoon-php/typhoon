<?php

declare(strict_types=1);

namespace Typhoon\Type\Internal;

use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 */
final class IterableType implements Type
{
    public function __construct(
        private readonly Type $key,
        private readonly Type $value,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->iterable($this, $this->key, $this->value);
    }
}
