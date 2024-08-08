<?php

declare(strict_types=1);

namespace Typhoon\Type\Internal;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 */
final class NamedObjectType implements Type
{
    /**
     * @param list<Type> $arguments
     */
    public function __construct(
        private readonly NamedClassId|AnonymousClassId $class,
        private readonly array $arguments,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->namedObject($this, $this->class, $this->arguments);
    }
}
