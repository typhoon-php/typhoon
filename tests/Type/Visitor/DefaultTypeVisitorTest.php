<?php

declare(strict_types=1);

namespace Typhoon\Type\Visitor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Typhoon\Type\Type;

#[CoversClass(DefaultTypeVisitor::class)]
final class DefaultTypeVisitorTest extends TestCase
{
    public function testItCoversAllMethods(): void
    {
        $this->expectNotToPerformAssertions();

        /** @phpstan-ignore expr.resultUnused */
        new /** @extends DefaultTypeVisitor<null> */ class extends DefaultTypeVisitor {
            protected function default(Type $type): mixed
            {
                return null;
            }
        };
    }
}
