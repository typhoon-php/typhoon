<?php

declare(strict_types=1);

namespace Typhoon\Reflection\NameContext;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NameContext::class)]
#[CoversClass(UnqualifiedName::class)]
#[CoversClass(QualifiedName::class)]
#[CoversClass(FullyQualifiedName::class)]
#[CoversClass(RelativeName::class)]
final class NameContextFunctionalTest extends TestCase
{
    public function testItCorrectlyResolvesClassNames(): void
    {
        NameContextFunctionalTester::test(__DIR__ . '/functional/classes.php');
    }

    public function testItCorrectlyResolvesConstantNames(): void
    {
        NameContextFunctionalTester::test(__DIR__ . '/functional/constants.php');
    }
}
