<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @psalm-immutable
 */
final class AtClass
{
    /**
     * @var class-string
     */
    public readonly string $name;

    /**
     * @internal
     * @psalm-internal Typhoon\Type
     * @param class-string $name
     */
    public function __construct(
        string $name,
    ) {
        $this->name = $name;
    }
}
