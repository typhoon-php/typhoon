<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Type;

use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeAlias;

/**
 * @psalm-api
 * @psalm-immutable
 * @template-covariant TKey
 * @template-covariant TValue
 * @template TSend
 * @template-covariant TReturn
 * @extends TypeAlias<\Generator<TKey, TValue, TSend, TReturn>>
 */
final class GeneratorT extends TypeAlias
{
    /**
     * @param Type<TKey>    $keyType
     * @param Type<TValue>  $valueType
     * @param Type<TSend>   $sendType
     * @param Type<TReturn> $returnType
     */
    public function __construct(
        public readonly Type $keyType = new MixedT(),
        public readonly Type $valueType = new MixedT(),
        public readonly Type $sendType = new MixedT(),
        public readonly Type $returnType = new MixedT(),
    ) {
    }

    public function type(): Type
    {
        /** @var NamedObjectT<\Generator<TKey, TValue, TSend, TReturn>> */
        return new NamedObjectT(
            \Generator::class,
            $this->keyType,
            $this->valueType,
            $this->sendType,
            $this->returnType,
        );
    }
}
