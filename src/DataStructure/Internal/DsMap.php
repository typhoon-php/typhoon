<?php

declare(strict_types=1);

namespace Typhoon\DataStructure\Internal;

use Ds\Map;
use Typhoon\DataStructure\MutableMap;

/**
 * @internal
 * @psalm-internal Typhoon\DataStructure
 * @template K
 * @template V
 * @extends MutableMap<K, V>
 */
final class DsMap extends MutableMap
{
    public function __construct(
        private Map $map = new Map(),
    ) {}

    public function contains(mixed $key): bool
    {
        return $this->map->hasKey(Encoder::encodeDs($key));
    }

    public function getOr(mixed $key, callable $or): mixed
    {
        /** @var V|DsGet::Default */
        $value = $this->map->get(Encoder::encodeDs($key), DsGet::Default);

        return $value === DsGet::Default ? $or() : $value;
    }

    public function usortKV(callable $comparator): static
    {
        // TODO make more efficient
        return new self(new Map(self::of($this)->usortKV($comparator)));
    }

    public function put(mixed $key, mixed $value): void
    {
        $this->map->put(Encoder::encodeDs($key), $value);
    }

    public function remove(mixed ...$keys): void
    {
        foreach ($keys as $key) {
            $this->map->remove(Encoder::encodeDs($key));
        }
    }

    public function clear(): void
    {
        $this->map->clear();
    }

    public function getIterator(): \Generator
    {
        foreach ($this->map as $key => $value) {
            if ($key instanceof DsHashable) {
                yield $key->object => $value;
            } else {
                yield $key => $value;
            }
        }
    }

    public function __clone()
    {
        $this->map = $this->map->copy();
    }
}
