<?php

declare(strict_types=1);

namespace Typhoon\Type\Visitor;

use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @api
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
