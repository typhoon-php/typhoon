<?php

declare(strict_types=1);

namespace Typhoon\Collection;

/**
 * @api
 * @template-covariant TValue
 * @extends Collection<non-negative-int, TValue>
 */
interface List_ extends Collection
{
    /**
     * @no-named-arguments
     * @template TNewValue
     * @param TNewValue ...$values
     * @return static<TValue|TNewValue>
     */
    public function with(mixed ...$values): static;

    /**
     * @return list<TValue>
     */
    public function toArray(): array;

    /**
     * @return self<TValue>
     */
    public function toMutable(): self;
}
