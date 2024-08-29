<?php

declare(strict_types=1);

namespace Typhoon\DataStructure;

/**
 * @api
 * @template-covariant K
 * @template-covariant V
 */
final class KeyValue
{
    /**
     * @param K $key
     * @param V $value
     */
    public function __construct(
        public readonly mixed $key,
        public readonly mixed $value,
    ) {}

    /**
     * @template NK
     * @param NK $key
     * @return self<NK, V>
     */
    public function withKey(mixed $key): self
    {
        return new self($key, $this->value);
    }

    /**
     * @template NV
     * @param NV $value
     * @return self<K, NV>
     */
    public function withValue(mixed $value): self
    {
        return new self($this->key, $value);
    }

    /**
     * @return self<V, K>
     */
    public function flip(): self
    {
        return new self($this->value, $this->key);
    }
}
