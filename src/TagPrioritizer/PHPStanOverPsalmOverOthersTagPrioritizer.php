<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\TagPrioritizer;

use ExtendedTypeSystem\TagPrioritizer;

/**
 * This prioritizer tells to consider {@phpstan-*} tags first, then {@psalm-*} tags, and finally the others.
 *
 * @api
 */
final class PHPStanOverPsalmOverOthersTagPrioritizer implements TagPrioritizer
{
    public function priorityFor(string $tagName): int
    {
        if (str_starts_with($tagName, '@phpstan')) {
            return 2;
        }

        if (str_starts_with($tagName, '@psalm')) {
            return 1;
        }

        return 0;
    }
}
