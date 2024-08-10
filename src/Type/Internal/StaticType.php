<?php

declare(strict_types=1);

namespace Typhoon\Type\Internal;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 */
final class StaticType implements Type
{
    /**
     * @param list<Type> $arguments
     */
    public function __construct(
        private readonly array $arguments,
        private readonly null|NamedClassId|AnonymousClassId $resolvedClass,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->static($this, $this->arguments, $this->resolvedClass);
    }
}
