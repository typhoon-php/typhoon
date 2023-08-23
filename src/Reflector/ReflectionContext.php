<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use Typhoon\Reflection\ClassLocator;
use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\ReflectionException;
use Typhoon\Reflection\Resource;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ReflectionContext
{
    private readonly Reflections $reflections;

    public function __construct(
        private readonly ClassLocator $classLocator,
        private readonly ReflectionCache $cache,
        private readonly PhpParserReflector $phpParserReflector,
        private readonly NativeReflectionReflector $nativeReflectionReflector,
    ) {
        $this->reflections = new Reflections();
    }

    public function reflectResource(Resource $resource): void
    {
        if ($this->cache->hasFile($resource->file)) {
            return;
        }

        $reflections = $this->phpParserReflector->reflectResource($resource, $this);
        $this->reflections->setFrom($reflections);
        $this->cache->setFileReflections($resource->file, $reflections);
    }

    /**
     * @param non-empty-string $name
     * @psalm-assert-if-true class-string $name
     */
    public function classExists(string $name): bool
    {
        $exists = $this->reflections->exists(ClassReflection::class, $name);

        if ($exists !== null) {
            return $exists;
        }

        $exists = class_exists($name, false) || interface_exists($name, false) || trait_exists($name, false)
            || $this->cache->hasReflection(ClassReflection::class, $name)
            || $this->classLocator->locateClass($name) !== null
            || class_exists($name) || interface_exists($name) || trait_exists($name);

        $this->reflections->setExists(ClassReflection::class, $name, $exists);

        return $exists;
    }

    /**
     * @template T of object
     * @param non-empty-string|class-string<T> $name
     * @psalm-assert class-string $name
     * @return ClassReflection<T>
     */
    public function reflectClass(string $name): ClassReflection
    {
        if (str_starts_with($name, 'class@anonymous')) {
            throw new ReflectionException('Anonymous classes are not supported yet.');
        }

        /** @var ClassReflection<T> */
        return $this->doReflectClass($name);
    }

    /**
     * @param non-empty-string $name
     */
    private function doReflectClass(string $name): ClassReflection
    {
        $reflection = $this->reflections->get(ClassReflection::class, $name);

        if ($reflection !== null) {
            return $reflection;
        }

        $cachedReflection = $this->cache->getReflection(ClassReflection::class, $name);

        if ($cachedReflection !== null) {
            $this->setReflectionContext($cachedReflection);
            $this->reflections->set($cachedReflection);

            return $cachedReflection;
        }

        $resource = $this->classLocator->locateClass($name);

        if ($resource !== null) {
            $this->reflectResource($resource);

            return $this->reflections->get(ClassReflection::class, $name)
                ?? throw new ReflectionException(sprintf('Class "%s" is not found in %s.', $name, $resource->file));
        }

        try {
            /** @psalm-suppress ArgumentTypeCoercion */
            $reflectionClass = new \ReflectionClass($name);
        } catch (\ReflectionException $exception) {
            throw new ReflectionException(sprintf('Class "%s" does not exist.', $name), previous: $exception);
        }

        $file = $reflectionClass->getFileName();

        if ($file !== false) {
            $this->reflectResource(new Resource($file, $reflectionClass->getExtensionName() ?: null));

            return $this->reflections->get(ClassReflection::class, $name)
                ?? throw new ReflectionException(sprintf('Class "%s" is not found in %s.', $name, $file));
        }

        $reflection = $this->nativeReflectionReflector->reflectClass($reflectionClass, $this);
        $this->reflections->set($reflection);

        $this->cache->setStandaloneReflection($reflection);

        return $reflection;
    }

    private function setReflectionContext(ClassReflection $classReflection): void
    {
        $reflectionContext = $this;

        (function () use ($reflectionContext): void {
            /** @psalm-suppress UndefinedThisPropertyAssignment */
            $this->reflectionContext = $reflectionContext;
        })->call($classReflection);
    }
}
