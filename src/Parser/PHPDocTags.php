<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Parser;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem
 */
final class PHPDocTags
{
    /**
     * @param list<PhpDocTagNode> $tags
     */
    public function __construct(
        private readonly array $tags,
    ) {
    }

    /**
     * @template TTagValue of PhpDocTagValueNode
     * @param class-string<TTagValue> $class
     * @return \Generator<string, TTagValue>
     */
    public function findTagValues(string $class): \Generator
    {
        foreach ($this->tags as $tag) {
            if ($tag->value instanceof $class) {
                yield $tag->name => $tag->value;
            }
        }
    }

    /**
     * @template TTagValue of PhpDocTagValueNode
     * @param class-string<TTagValue> $class
     * @return ?TTagValue
     */
    public function findTagValue(string $class): ?PhpDocTagValueNode
    {
        foreach ($this->findTagValues($class) as $tag) {
            return $tag;
        }

        return null;
    }
}
