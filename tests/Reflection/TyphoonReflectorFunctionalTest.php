<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;
use Typhoon\ChangeDetector\InMemoryChangeDetector;
use Typhoon\DeclarationId\Id;
use Typhoon\Type\types;
use function Typhoon\Reflection\Internal\get_namespace;

#[CoversNothing]
final class TyphoonReflectorFunctionalTest extends TestCase
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
    public function testFiles(string $file): void
    {
        self::$reflector ??= TyphoonReflector::build();
        /** @psalm-suppress UnresolvableInclude */
        $test = require_once $file;
        \assert($test instanceof \Closure);

        $test(self::$reflector, $this);
    }

    /**
     * @return \Generator<non-empty-string, array{non-empty-string, ?non-empty-string, mixed}>
     */
    public static function definedConstantsWithoutNan(): \Generator
    {
        foreach (get_defined_constants(categorize: true) as $category => $constants) {
            foreach ($constants as $name => $value) {
                if ($name === 'NAN') {
                    continue;
                }

                $extension = $category === 'user' ? null : (new \ReflectionExtension($category))->name;

                \assert($name !== '');
                \assert($extension !== '');

                yield $name => [$name, $extension, $value];
            }
        }
    }

    /**
     * @param non-empty-string $name
     */
    #[DataProvider('definedConstantsWithoutNan')]
    public function testDefinedConstantsWithoutNan(string $name, ?string $expectedExtension, mixed $expectedValue): void
    {
        self::$reflector ??= TyphoonReflector::build();

        $constant = self::$reflector->reflectConstant($name);

        self::assertEquals(Id::constant($name), $constant->id);
        self::assertSame($expectedValue, $constant->evaluate());
        self::assertEquals(types::value($expectedValue), $constant->type());
        self::assertNull($constant->type(TypeKind::Native));
        self::assertNull($constant->type(TypeKind::Annotated));
        self::assertNull($constant->type(TypeKind::Tentative));
        self::assertEquals(types::value($expectedValue), $constant->type(TypeKind::Inferred));
        self::assertSame($expectedExtension, $constant->extension());
        self::assertSame($expectedExtension !== null, $constant->isInternallyDefined());
        self::assertNull($constant->phpDoc());
        self::assertNull($constant->location());
        self::assertNull($constant->deprecation());
        self::assertSame(get_namespace($name), $constant->namespace());
        self::assertEquals(new InMemoryChangeDetector(), $constant->changeDetector());
    }
}
