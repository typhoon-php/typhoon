<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpDocParser;

/**
 * @api
 */
interface TagPrioritizer
{
    /**
     * @param non-empty-string $tagName tag name including @
     * @return int the higher the number, the earlier given tag will be considered
     */
    public function priorityFor(string $tagName): int;
}
