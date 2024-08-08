<?php

declare(strict_types=1);

namespace Typhoon\Type\Internal;

use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 */
final class OffsetType implements Type
{
    public function __construct(
        private readonly Type $array,
        private readonly Type $key,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->offset($this, $this->array, $this->key);
    }
}
