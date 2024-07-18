<?php

declare(strict_types=1);

namespace Typhoon\Type\Internal;

use Typhoon\DeclarationId\ConstId;
use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @psalm-immutable
 * @implements Type<mixed>
 */
final class ConstType implements Type
{
    public function __construct(
        private readonly ConstId $const,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->const($this, $this->const);
    }
}
