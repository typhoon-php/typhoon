<?php

declare(strict_types=1);

namespace Typhoon\Type\Internal;

use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @psalm-immutable
 * @implements Type<mixed>
 */
final class KeyType implements Type
{
    public function __construct(
        private readonly Type $type,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->key($this, $this->type);
    }
}
