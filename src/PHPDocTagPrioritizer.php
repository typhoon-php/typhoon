<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

/**
 * @psalm-api
 */
interface PHPDocTagPrioritizer
{
    /**
     * @param string $tagName tag name including @
     * @return int the higher the number, the earlier given tag will be considered
     * @see PHPStanOverPsalmOverOtherPHPDocTagPrioritizer
     */
    public function priorityFor(string $tagName): int;
}
