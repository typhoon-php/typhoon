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
final class AliasType implements Type
{
    /**
     * @param non-empty-string $class
     * @param non-empty-string $name
     * @param list<Type> $arguments
     */
    public function __construct(
        private readonly string $class,
        private readonly string $name,
        private readonly array $arguments,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->alias($this, $this->class, $this->name, $this->arguments);
    }
}
