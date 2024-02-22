<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @template-covariant TType
 * @implements Type<TType>
 */
final class AliasType implements Type
{
    /**
     * @var non-empty-string
     */
    public readonly string $class;

    /**
     * @var non-empty-string
     */
    public readonly string $name;

    /**
     * @internal
     * @psalm-internal Typhoon\Type
     * @param non-empty-string $class
     * @param non-empty-string $name
     */
    public function __construct(
        string $class,
        string $name,
    ) {
        $this->class = $class;
        $this->name = $name;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitAlias($this);
    }
}
