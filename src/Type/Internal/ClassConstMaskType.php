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
final class ClassConstMaskType implements Type
{
    public function __construct(
        private readonly Type $class,
        private readonly string $namePrefix,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->classConstMask($this, $this->class, $this->namePrefix);
    }
}
