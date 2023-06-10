<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

use ExtendedTypeSystem\Reflection\ChangeDetector\FileChangeDetector;
use ExtendedTypeSystem\Reflection\ChangeDetector\PhpVersionChangeDetector;
use ExtendedTypeSystem\Reflection\ClassLocator\ClassLocatorChain;
use ExtendedTypeSystem\Reflection\Reflector\NativeReflector;
use ExtendedTypeSystem\Reflection\Reflector\PhpParserReflector;
use ExtendedTypeSystem\Reflection\Reflector\ReflectionCache;
use ExtendedTypeSystem\Reflection\Reflector\Reflections;

final class Reflector
{
    private readonly Reflections $reflections;

    private readonly \ReflectionMethod $classReflectionLoad;

    public function __construct(
        private readonly ClassLocator $classLocator = new ClassLocatorChain([
            new ClassLocator\ComposerClassLocator(),
            new ClassLocator\PhpStormStubsClassLocator(),
        ]),
        private readonly ReflectionCache $cache = new ReflectionCache(),
        private readonly NativeReflector $nativeReflector = new NativeReflector(),
        private readonly PhpParserReflector $phpParserReflector = new PhpParserReflector(),
    ) {
        $this->reflections = new Reflections();
        $this->classReflectionLoad = new \ReflectionMethod(ClassReflection::class, 'load');
    }

    /**
     * @param non-empty-string $file
     */
    public function parseFile(string $file): void
    {
        if ($this->cache->hasFile($file)) {
            return;
        }

        $code = file_get_contents($file);

        if ($code === false) {
            throw new \RuntimeException();
        }

        $changeDetector = FileChangeDetector::fromContents($file, $code);
        $reflections = $this->phpParserReflector->parseCode($code, $this);
        $this->reflections->addFrom($reflections);
        $this->cache->setFileReflections($file, $reflections, $changeDetector);
    }

    /**
     * @param non-empty-string $name
     * @psalm-assert-if-true class-string $name
     */
    public function classExists(string $name): bool
    {
        return $this->reflections->has(ClassReflection::class, $name)
            || $this->cache->hasReflection(ClassReflection::class, $name)
            || $this->classLocator->locateClass($name) !== null
            || class_exists($name) || interface_exists($name) || trait_exists($name);
    }

    /**
     * @template T of object
     * @param non-empty-string|class-string<T> $name
     * @psalm-assert class-string $name
     * @return ClassReflection<T>
     */
    public function reflectClass(string $name): ClassReflection
    {
        /** @var ClassReflection<T> */
        $reflection = $this->doReflectClass($name);
        $this->classReflectionLoad->invoke($reflection, $this);

        return $reflection;
    }

    /**
     * @template T of object
     * @param non-empty-string|class-string<T> $name
     * @psalm-assert class-string $name
     * @return ClassReflection<T>
     */
    private function doReflectClass(string $name): ClassReflection
    {
        if (str_starts_with($name, 'class@anonymous')) {
            throw new \LogicException();
        }

        /** @var ?ClassReflection<T> */
        $reflection = $this->reflections->get(ClassReflection::class, $name);

        if ($reflection !== null) {
            return $reflection;
        }

        /** @var ?ClassReflection<T> */
        $cachedReflection = $this->cache->getReflection(ClassReflection::class, $name);

        if ($cachedReflection !== null) {
            $this->reflections->add($name, $cachedReflection);

            return $cachedReflection;
        }

        $file = $this->classLocator->locateClass($name);

        if ($file !== null) {
            $this->parseFile($file);

            /** @var ClassReflection<T> */
            return $this->reflections->get(ClassReflection::class, $name) ?? throw new \LogicException();
        }

        try {
            /** @psalm-suppress ArgumentTypeCoercion */
            $reflectionClass = new \ReflectionClass($name);
        } catch (\ReflectionException) {
            throw new \LogicException();
        }

        $file = $reflectionClass->getFileName();

        if ($file !== false) {
            $this->parseFile($file);

            /** @var ClassReflection<T> */
            return $this->reflections->get(ClassReflection::class, $name) ?? throw new \LogicException();
        }

        $this->reflections->addLazy(ClassReflection::class, $name, fn (): ClassReflection => $this->nativeReflector->reflectClass($reflectionClass));
        /** @var ClassReflection<T> */
        $reflection = $this->reflections->get(ClassReflection::class, $name) ?? throw new \LogicException();

        $changeDetector = new PhpVersionChangeDetector($reflectionClass->getExtensionName() ?: null);
        $this->cache->setStandaloneReflection($name, $reflection, $changeDetector);

        return $reflection;
    }
}
