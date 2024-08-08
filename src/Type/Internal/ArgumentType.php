<?php

declare(strict_types=1);

namespace Typhoon\Type\Internal;

use Typhoon\DeclarationId\ParameterId;
use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 */
final class ArgumentType implements Type
{
    public function __construct(
        private readonly ParameterId $parameter,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->argument($this, $this->parameter);
    }
}
