<?php

declare(strict_types=1);

namespace Typhoon\Type\Internal;

use Typhoon\Type\Parameter;
use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @implements Type<callable>
 */
final class CallableType implements Type
{
    /**
     * @param list<Parameter> $parameters
     */
    public function __construct(
        private readonly array $parameters,
        private readonly Type $return,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->callable($this, $this->parameters, $this->return);
    }
}
