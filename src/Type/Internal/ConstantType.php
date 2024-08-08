<?php

declare(strict_types=1);

namespace Typhoon\Type\Internal;

use Typhoon\DeclarationId\ConstantId;
use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 */
final class ConstantType implements Type
{
    public function __construct(
        private readonly ConstantId $constant,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->constant($this, $this->constant);
    }
}
