<?php

declare(strict_types=1);

namespace Typhoon\Type;

use PHPUnit\Framework\Assert;
use Typhoon\Type\Visitor\DefaultTypeVisitor;

/**
 * @extends DefaultTypeVisitor<null>
 */
abstract class RoutingTester extends DefaultTypeVisitor
{
    protected function default(Type $type): never
    {
        Assert::fail('Type was routed to a different method');
    }
}
