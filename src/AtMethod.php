<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 */
final class AtMethod
{
    /**
     * @var class-string
     */
    public readonly string $class;

    /**
     * @var non-empty-string
     */
    public readonly string $name;

    /**
     * @internal
     * @psalm-internal Typhoon\Type
     * @param class-string $class
     * @param non-empty-string $name
     */
    public function __construct(
        string $class,
        string $name,
    ) {
        $this->class = $class;
        $this->name = $name;
    }
}
