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
     * @param list<TypeVisitor<Type>> $resolvers
     * @return TypeVisitor<Type>
     */
    public static function from(array $resolvers): TypeVisitor
    {
        return match (\count($resolvers)) {
            0 => new IdentityTypeResolver(),
            1 => $resolvers[0],
            default => new self($resolvers),
        };
    }

    /**
     * @param non-empty-list<TypeVisitor<Type>> $resolvers
     */
    private function __construct(
        private readonly array $resolvers,
    ) {}

    protected function default(Type $type): mixed
    {
        foreach ($this->resolvers as $resolver) {
            $type = $type->accept($resolver);
        }

        return $type;
    }
}
