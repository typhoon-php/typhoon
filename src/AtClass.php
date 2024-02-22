<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 */
final class AtClass
{
    /**
     * @var non-empty-string
     */
    public readonly string $name;

    /**
     * @internal
     * @psalm-internal Typhoon\Type
     * @param non-empty-string $name
     */
    public function __construct(
        string $name,
    ) {
        $this->name = $name;
    }
}
