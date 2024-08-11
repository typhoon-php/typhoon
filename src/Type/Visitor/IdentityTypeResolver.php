<?php

declare(strict_types=1);

namespace Typhoon\Type\Visitor;

use Typhoon\Type\Type;

/**
 * @api
 * @extends DefaultTypeVisitor<Type>
 */
final class IdentityTypeResolver extends DefaultTypeVisitor
{
    protected function default(Type $type): Type
    {
        return $type;
    }
}
