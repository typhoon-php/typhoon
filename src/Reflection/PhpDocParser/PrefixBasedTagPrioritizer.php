<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpDocParser;

/**
 * @api
 */
final class PrefixBasedTagPrioritizer implements TagPrioritizer
{
    /**
     * @param array<non-empty-string, int> $prefixPriorities
     */
    public function __construct(
        private readonly array $prefixPriorities = ['@psalm' => 2, '@phpstan' => 1],
    ) {}

    public function priorityFor(string $tagName): int
    {
        foreach ($this->prefixPriorities as $prefix => $priority) {
            if (str_starts_with($tagName, $prefix)) {
                return $priority;
            }
        }

        return 0;
    }
}
