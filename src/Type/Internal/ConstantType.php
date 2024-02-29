<?php

declare(strict_types=1);

namespace Typhoon\Type\Internal;

use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @implements Type<mixed>
 */
final class ConstantType implements Type
{
    /**
     * @param non-empty-string $name
     */
    public function __construct(
        private readonly string $name,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->constant($this, $this->name);
    }
}
