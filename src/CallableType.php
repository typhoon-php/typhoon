<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @template-covariant TReturn
 * @implements Type<callable(): TReturn>
 */
final class CallableType implements Type
{
    /**
     * @param list<Parameter> $parameters
     * @param Type<TReturn> $returnType
     */
    public function __construct(
        private readonly array $parameters,
        private readonly Type $returnType,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->callable($this, $this->parameters, $this->returnType);
    }
}
