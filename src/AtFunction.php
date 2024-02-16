<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 */
final class AtFunction
{
    /**
     * @var callable-string
     */
    public readonly string $name;

    /**
     * @internal
     * @psalm-internal Typhoon\Type
     * @param callable-string $name
     */
    public function __construct(
        string $name,
    ) {
        $this->name = $name;
    }
}
