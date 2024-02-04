<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PhpParser\Parser as PhpParser;
use PhpParser\ParserFactory;
use Psr\SimpleCache\CacheInterface;
use Typhoon\Reflection\ClassLocator\ClassLocatorChain;
use Typhoon\Reflection\ClassLocator\ComposerClassLocator;
use Typhoon\Reflection\ClassLocator\NativeReflectionLocator;
use Typhoon\Reflection\ClassLocator\PhpStormStubsClassLocator;
use Typhoon\Reflection\ClassReflection\ClassReflector;
use Typhoon\Reflection\NameContext\AnonymousClassName;
use Typhoon\Reflection\NativeReflector\NativeReflector;
use Typhoon\Reflection\PhpDocParser\PhpDocParser;
use Typhoon\Reflection\PhpDocParser\PHPStanOverPsalmOverOthersTagPrioritizer;
use Typhoon\Reflection\PhpDocParser\TagPrioritizer;
use Typhoon\Reflection\PhpParserReflector\PhpParserReflector;
use Typhoon\Reflection\ReflectionStorage\ChangeDetector;
use Typhoon\Reflection\ReflectionStorage\ReflectionStorage;

/**
 * @api
 */
final class TyphoonReflector implements ClassReflector
{
    /**
     * @var array<non-empty-string, true>
     */
    private array $reflectedResources = [];

    private function __construct(
        private readonly ReflectionStorage $reflectionStorage,
        private readonly PhpParserReflector $phpParserReflector,
        private readonly NativeReflector $nativeReflector,
        private readonly ClassLocator $classLocator,
    ) {}

    /**
     * @param ?array<ClassLocator> $classLocators
     */
    public static function build(
        ?CacheInterface $cache = null,
        bool $detectChanges = true,
        ?array $classLocators = null,
        TagPrioritizer $tagPrioritizer = new PHPStanOverPsalmOverOthersTagPrioritizer(),
        ?PhpParser $phpParser = null,
    ): self {
        return new self(
            reflectionStorage: new ReflectionStorage($cache, $detectChanges),
            phpParserReflector: new PhpParserReflector(
                phpParser: $phpParser ?? (new ParserFactory())->createForNewestSupportedVersion(),
                phpDocParser: new PhpDocParser($tagPrioritizer),
            ),
            nativeReflector: new NativeReflector(),
            classLocator: new ClassLocatorChain($classLocators ?? self::defaultClassLocators()),
        );
    }

    /**
     * @return non-empty-list<ClassLocator>
     */
    public static function defaultClassLocators(): array
    {
        $classLocators = [];

        if (ComposerClassLocator::isSupported()) {
            $classLocators[] = new ComposerClassLocator();
        }

        if (PhpStormStubsClassLocator::isSupported()) {
            $classLocators[] = new PhpStormStubsClassLocator();
        }

        $classLocators[] = new NativeReflectionLocator();

        return $classLocators;
    }

    /**
     * @psalm-assert-if-true class-string $name
     */
    public function classExists(string $name): bool
    {
        if ($name === '') {
            return false;
        }

        if (class_exists($name, false) || interface_exists($name, false) || trait_exists($name, false)) {
            return true;
        }

        if (str_contains($name, '@')) {
            return false;
        }

        /** @var non-empty-string $name */
        return $this->reflectionStorage->exists(
            class: ClassReflection::class,
            name: $name,
            loader: function () use ($name): void { $this->loadClass($name); },
        );
    }

    /**
     * @psalm-suppress InvalidReturnType, InvalidReturnStatement
     */
    public function reflectClass(string|object $nameOrObject): ClassReflection
    {
        if (\is_object($nameOrObject)) {
            $name = $nameOrObject::class;
        } else {
            \assert($nameOrObject !== '');
            $name = $nameOrObject;
        }

        $anonymousName = AnonymousClassName::tryFromString($name);

        if ($anonymousName !== null) {
            $reflection = $this->phpParserReflector->reflectAnonymousClass($anonymousName, $this);
            $reflection->__initialize($this);

            return $reflection;
        }

        $reflection = $this->reflectionStorage->get(
            class: ClassReflection::class,
            name: $name,
            loader: function () use ($name): void { $this->loadClass($name); },
        );
        $reflection->__initialize($this);

        return $reflection;
    }

    /**
     * @param non-empty-string $name
     */
    private function loadClass(string $name): void
    {
        $location = $this->classLocator->locateClass($name);

        if ($location instanceof Resource) {
            $this->reflectResource($location);

            return;
        }

        if ($location instanceof \ReflectionClass) {
            $this->reflectionStorage->setReflector(
                class: ClassReflection::class,
                name: $name,
                reflector: fn(): ClassReflection => $this->nativeReflector->reflectClass($location),
                changeDetector: ChangeDetector::fromReflection($location),
            );
        }
    }

    private function reflectResource(Resource $resource): void
    {
        if (!isset($this->reflectedResources[$resource->file])) {
            $this->phpParserReflector->reflectResource($resource, $this->reflectionStorage, $this);
            $this->reflectedResources[$resource->file] = true;
        }
    }
}
