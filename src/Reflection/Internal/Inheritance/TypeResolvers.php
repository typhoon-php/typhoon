<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Inheritance;

use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;
use Typhoon\Type\Visitor\DefaultTypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal\Inheritance
 * @extends DefaultTypeVisitor<Type>
 */
final class TypeResolvers extends DefaultTypeVisitor
{
    /**
     * @param iterable<TypeVisitor<Type>> $resolvers
     */
    public function __construct(
        private readonly iterable $resolvers = [],
    ) {}

    protected function default(Type $type): mixed
    {
        foreach ($this->resolvers as $resolver) {
            $type = $type->accept($resolver);
        }

        return $type;
    }
}
