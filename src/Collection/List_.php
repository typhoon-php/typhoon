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

    public function sort(int $flags = SORT_REGULAR): static;

    public function sortDesc(int $flags = SORT_REGULAR): static;

    /**
     * @psalm-suppress InvalidTemplateParam
     * @param callable(TValue, TValue, non-negative-int, non-negative-int): int $comparator
     */
    public function sortBy(callable $comparator): static;
}
