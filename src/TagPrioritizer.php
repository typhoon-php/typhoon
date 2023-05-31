<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

use ExtendedTypeSystem\Reflection\TagPrioritizer\PHPStanOverPsalmOverOthersTagPrioritizer;

/**
 * @api
 */
interface TagPrioritizer
{
    /**
     * @param string $tagName tag name including @
     * @return int the higher the number, the earlier given tag will be considered
     * @see PHPStanOverPsalmOverOthersTagPrioritizer
     */
    public function priorityFor(string $tagName): int;
}
