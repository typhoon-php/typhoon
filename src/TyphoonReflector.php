<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Psr\SimpleCache\CacheInterface;
use Typhoon\Reflection\ClassLoader\ClassLoaderChain;
use Typhoon\Reflection\ClassLoader\ComposerClassLoader;
use Typhoon\Reflection\ClassLoader\NativeReflectionClassLoader;
use Typhoon\Reflection\ClassLoader\PhpStormStubsClassLoader;
use Typhoon\Reflection\PhpDocParser\PhpDocParser;
use Typhoon\Reflection\PhpDocParser\PHPStanOverPsalmOverOthersTagPrioritizer;
use Typhoon\Reflection\PhpDocParser\TagPrioritizer;
use Typhoon\Reflection\PhpParser\PhpParser;
use Typhoon\Reflection\Reflector\Cache\ChangeDetectingReflectionCache;
use Typhoon\Reflection\Reflector\Cache\NullReflectionCache;
use Typhoon\Reflection\Reflector\Cache\SimpleReflectionCache;
use Typhoon\Reflection\Reflector\Context;

/**
 * @api
 */
final class TyphoonReflector
{
    private function __construct(
        private readonly Context $context,
    ) {}

    /**
     * @param ?array<ClassLoader> $classLoaders
     */
    public static function build(
        ?CacheInterface $cache = null,
        bool $detectChanges = true,
        ?array $classLoaders = null,
        TagPrioritizer $tagPrioritizer = new PHPStanOverPsalmOverOthersTagPrioritizer(),
    ): self {
        $classLoaders ??= self::defaultClassLoaders();

        if ($cache === null) {
            $reflectionCache = new NullReflectionCache();
        } else {
            $reflectionCache = new SimpleReflectionCache($cache);

            if ($detectChanges) {
                $reflectionCache = new ChangeDetectingReflectionCache($reflectionCache);
            }
        }

        return new self(new Context(
            classLoader: new ClassLoaderChain($classLoaders),
            phpParser: new PhpParser(),
            phpDocParser: new PhpDocParser($tagPrioritizer),
            cache: $reflectionCache,
        ));
    }

    /**
     * @return list<ClassLoader>
     */
    public static function defaultClassLoaders(): array
    {
        $classLoaders = [];

        if (ComposerClassLoader::isSupported()) {
            $classLoaders[] = new ComposerClassLoader();
        }

        if (PhpStormStubsClassLoader::isSupported()) {
            $classLoaders[] = new PhpStormStubsClassLoader();
        }

        $classLoaders[] = new NativeReflectionClassLoader();

        return $classLoaders;
    }

    /**
     * @psalm-assert-if-true class-string $name
     */
    public function classExists(string $name): bool
    {
        return $this->context->classExists($name);
    }

    /**
     * @template T of object
     * @param string|class-string<T> $name
     * @psalm-assert class-string $name
     * @return ClassReflection<T>
     */
    public function reflectClass(string $name): ClassReflection
    {
        /** @var ClassReflection<T> */
        return $this->context->reflectClass($name);
    }

    /**
     * @template T of object
     * @param T $object
     * @return ClassReflection<T>
     */
    public function reflectObject(object $object): ClassReflection
    {
        return $this->context->reflectClass($object::class);
    }
}
