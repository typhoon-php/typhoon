<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Visitor;

use Typhoon\Type\Type;
use Typhoon\Type\Visitor\DefaultTypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Visitor
 * @extends DefaultTypeVisitor<bool>
 */
final class IsNever extends DefaultTypeVisitor
{
    public function never(Type $type): mixed
    {
        return true;
    }

    protected function default(Type $type): mixed
    {
        return false;
    }
}
