<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\ClassLocator\ClassLocatorChain;
use Typhoon\Reflection\ClassLocator\ComposerClassLocator;
use Typhoon\Reflection\ClassLocator\PhpStormStubsClassLocator;
use Typhoon\Reflection\PhpDocParser\PhpDocParser;
use Typhoon\Reflection\Reflector\FileReflectionCache;
use Typhoon\Reflection\Reflector\NativeReflectionReflector;
use Typhoon\Reflection\Reflector\NullReflectionCache;
use Typhoon\Reflection\Reflector\PhpParserReflector;
use Typhoon\Reflection\Reflector\ReflectionCache;
use Typhoon\Reflection\Reflector\ReflectionContext;
use Typhoon\Reflection\TagPrioritizer\PHPStanOverPsalmOverOthersTagPrioritizer;

/**
 * @api
 */
final class Reflector
{
    private ?ReflectionContext $context = null;

    private function __construct(
        private readonly ClassLocator $classLocator,
        private readonly ReflectionCache $cache,
        private readonly PhpParserReflector $phpParserReflector,
        private readonly NativeReflectionReflector $nativeReflectionReflector,
    ) {}

    public static function build(
        bool $cache = true,
        ?string $cacheDirectory = null,
        bool $detectChanges = true,
        ?ClassLocator $classLocator = null,
        TagPrioritizer $tagPrioritizer = new PHPStanOverPsalmOverOthersTagPrioritizer(),
    ): self {
        return new self(
            classLocator: $classLocator ?? self::defaultClassLocator(),
            cache: $cache ? new FileReflectionCache($cacheDirectory, $detectChanges) : new NullReflectionCache(),
            phpParserReflector: new PhpParserReflector(
                phpDocParser: new PhpDocParser(
                    tagPrioritizer: $tagPrioritizer,
                ),
            ),
            nativeReflectionReflector: new NativeReflectionReflector(),
        );
    }

    private static function defaultClassLocator(): ClassLocator
    {
        $classLocators = [];

        if (ComposerClassLocator::isSupported()) {
            $classLocators[] = new ComposerClassLocator();
        }

        if (PhpStormStubsClassLocator::isSupported()) {
            $classLocators[] = new PhpStormStubsClassLocator();
        }

        return new ClassLocatorChain($classLocators);
    }

    public function reflectResource(Resource $resource): void
    {
        $this->context()->reflectResource($resource);
    }

    /**
     * @template T of object
     * @param string|class-string<T> $name
     * @psalm-assert class-string $name
     * @return ClassReflection<T>
     */
    public function reflectClass(string $name): ClassReflection
    {
        if ($name === '') {
            throw new \InvalidArgumentException('Class name must not be empty.');
        }

        /** @var ClassReflection<T> */
        return $this->context()->reflectClass($name);
    }

    public function clearCache(): void
    {
        $this->cache->clear();
    }

    private function context(): ReflectionContext
    {
        return $this->context ??= new ReflectionContext(
            classLocator: $this->classLocator,
            cache: $this->cache,
            phpParserReflector: $this->phpParserReflector,
            nativeReflectionReflector: $this->nativeReflectionReflector,
        );
    }
}
