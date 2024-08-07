<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

#[CoversClass(TyphoonReflector::class)]
#[CoversClass(AliasReflection::class)]
#[CoversClass(AttributeReflection::class)]
#[CoversClass(ClassConstantReflection::class)]
#[CoversClass(ClassReflection::class)]
#[CoversClass(ConstantReflection::class)]
#[CoversClass(FunctionReflection::class)]
#[CoversClass(MethodReflection::class)]
#[CoversClass(ParameterReflection::class)]
#[CoversClass(PropertyReflection::class)]
#[CoversClass(TemplateReflection::class)]
#[CoversClass(Location::class)]
final class FunctionalTest extends TestCase
{
    private static ?TyphoonReflector $reflector = null;

    /**
     * @return \Generator<string, array{string}>
     */
    public static function files(): \Generator
    {
        foreach (Finder::create()->in(__DIR__ . '/functional_tests')->name('*.php') as $file) {
            yield substr($file->getRelativePathname(), 0, -4) => [$file->getPathname()];
        }
    }

    #[DataProvider('files')]
    public function test(string $file): void
    {
        self::$reflector ??= TyphoonReflector::build();
        /** @psalm-suppress UnresolvableInclude */
        $test = require_once $file;
        \assert($test instanceof \Closure);

        $test(self::$reflector, $this);
    }
}
