<?php

declare(strict_types=1);

namespace Typhoon\Type\Internal;

use Typhoon\DeclarationId\AliasId;
use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 */
final class AliasType implements Type
{
    /**
     * @param list<Type> $arguments
     */
    public function __construct(
        private readonly AliasId $alias,
        private readonly array $arguments,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->alias($this, $this->alias, $this->arguments);
    }
}
