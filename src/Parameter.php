<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @template-covariant TType
 */
final class Parameter
{
    /**
     * @var Type<TType>
     */
    public readonly Type $type;

    public readonly bool $hasDefault;

    public readonly bool $variadic;

    public readonly bool $byReference;

    /**
     * @var ?non-empty-string
     */
    public readonly ?string $name;

    /**
     * @internal
     * @psalm-internal Typhoon\Type
     * @param Type<TType> $type
     * @param ?non-empty-string $name
     */
    public function __construct(
        Type $type = MixedType::type,
        bool $hasDefault = false,
        bool $variadic = false,
        bool $byReference = false,
        ?string $name = null,
    ) {
        \assert(!($hasDefault && $variadic), 'Parameter can be either default or variadic.');

        $this->type = $type;
        $this->hasDefault = $hasDefault;
        $this->variadic = $variadic;
        $this->byReference = $byReference;
        $this->name = $name;
    }
}
