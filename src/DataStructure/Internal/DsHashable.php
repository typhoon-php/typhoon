<?php

declare(strict_types=1);

namespace Typhoon\DataStructure\Internal;

use Ds\Hashable;

/**
 * @internal
 * @psalm-internal Typhoon\DataStructure\Internal
 * @template-covariant TObject of object
 */
final class DsHashable implements Hashable
{
    /**
     * @param TObject $object
     * @param non-empty-string $hash
     */
    public function __construct(
        public readonly object $object,
        private readonly string $hash,
    ) {}

    /**
     * @psalm-suppress PossiblyUnusedMethod, ParamNameMismatch, MissingParamType
     */
    public function equals($obj): bool
    {
        return $obj instanceof self && $this->hash === $obj->hash;
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function hash(): string
    {
        return $this->hash;
    }
}
