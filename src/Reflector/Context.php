<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use Typhoon\Reflection\AnonymousClassName;
use Typhoon\Reflection\ChangeDetector\FileChangeDetector;
use Typhoon\Reflection\ClassLoader;
use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\NameResolution\NameContext;
use Typhoon\Reflection\ParsingContext;
use Typhoon\Reflection\PhpDocParser\PhpDocParser;
use Typhoon\Reflection\PhpParser\PhpParser;
use Typhoon\Reflection\ReflectionContext;
use Typhoon\Reflection\ReflectionException;
use Typhoon\Reflection\Reflector\Cache\NullReflectionCache;
use function Typhoon\Reflection\Exceptionally\exceptionally;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class Context implements ParsingContext, ReflectionContext
{
    /**
     * @var array<non-empty-string, bool>
     */
    private array $parsedFiles = [];

    /**
     * @var array<class-string<RootReflection>, array<non-empty-string, false|RootReflection|callable(self): RootReflection>>
     */
    private array $reflections = [];

    private ?\ReflectionProperty $classReflectionContextProperty = null;

    public function __construct(
        private readonly ClassLoader $classLoader,
        private readonly PhpParser $phpParser,
        private readonly PhpDocParser $phpDocParser,
        private readonly ReflectionCache $cache = new NullReflectionCache(),
    ) {}

    public function parseFile(string $file, ?string $extension = null): void
    {
        \assert($file !== '');
        \assert($extension !== '');

        if (isset($this->parsedFiles[$file])) {
            return;
        }

        if ($this->cache->getResource($file) !== null) {
            $this->parsedFiles[$file] = true;

            return;
        }

        $code = exceptionally(static fn (): string|false => file_get_contents($file));
        $resource = new Resource(
            file: $file,
            extension: $extension,
            changeDetector: FileChangeDetector::fromContents($file, $code),
        );

        $nameContext = new NameContext();
        $nameContextVisitor = new NameContextVisitor($nameContext);
        $this->phpParser->parseAndTraverse($code, [
            new PhpDocParsingVisitor($this->phpDocParser),
            $nameContextVisitor,
            new DiscoveringVisitor(
                parsingContext: $this,
                nameContext: $nameContext,
                resource: $resource,
            ),
        ]);
        $this->cache->addResource($resource);
        $this->parsedFiles[$file] = true;
    }

    public function registerClassReflector(string $name, callable $reflector): void
    {
        $this->reflections[ClassReflection::class][$name] = $reflector;
    }

    public function classExists(string $name): bool
    {
        if ($name === '') {
            return false;
        }

        if (class_exists($name, false) || interface_exists($name, false) || trait_exists($name, false)) {
            return true;
        }

        if (isset($this->reflections[ClassReflection::class][$name])) {
            return $this->reflections[ClassReflection::class][$name] !== false;
        }

        try {
            $this->reflectClass($name);

            return true;
        } catch (ReflectionException) {
            return false;
        }
    }

    /**
     * @template T of object
     * @param string|class-string<T> $name
     * @psalm-assert class-string $name
     * @return ClassReflection<T>
     */
    public function reflectClass(string $name): ClassReflection
    {
        \assert($name !== '');

        $anonymousClassName = AnonymousClassName::tryFromString($name);

        if ($anonymousClassName !== null) {
            $name = $anonymousClassName->toStringWithoutRtdKeyCounter();
        }

        /** @var ClassReflection<T> */
        return $this->resolveReflection(ClassReflection::class, $name, function () use ($name, $anonymousClassName): void {
            if ($anonymousClassName !== null) {
                $this->parseFile($anonymousClassName->file);

                return;
            }

            $this->classLoader->loadClass($this, $name);
        }) ?? throw new ReflectionException();
    }

    /**
     * @template TReflection of RootReflection
     * @param class-string<TReflection> $class
     * @param non-empty-string $name
     * @param \Closure(): void $reflect
     * @return ?TReflection
     */
    private function resolveReflection(string $class, string $name, \Closure $reflect): ?RootReflection
    {
        $reflection = $this->reflections[$class][$name] ?? null;

        if ($reflection === false) {
            return null;
        }

        if ($reflection instanceof $class) {
            return $reflection;
        }

        if (\is_callable($reflection)) {
            /** @var TReflection */
            $reflection = $reflection($this);
            $this->cache->addReflection($reflection);

            return $this->reflections[$class][$name] = $reflection;
        }

        $cachedReflection = $this->cache->getReflection($class, $name);

        if ($cachedReflection !== null) {
            $this->loadCachedReflection($cachedReflection);

            /** @var TReflection */
            return $this->reflections[$class][$name] = $cachedReflection;
        }

        $this->reflections[$class][$name] = false;

        $reflect();

        /** @var false|RootReflection|callable(self): RootReflection */
        $reflection = $this->reflections[$class][$name] ?? false;

        if ($reflection instanceof $class) {
            return $reflection;
        }

        if (\is_callable($reflection)) {
            /** @var TReflection */
            $reflection = $reflection($this);
            $this->cache->addReflection($reflection);

            return $this->reflections[$class][$name] = $reflection;
        }

        return null;
    }

    private function loadCachedReflection(RootReflection $reflection): void
    {
        if (!$reflection instanceof ClassReflection) {
            return;
        }

        $property = $this->classReflectionContextProperty ??= new \ReflectionProperty(ClassReflection::class, 'reflectionContext');

        try {
            $property->setValue($reflection, $this);
        } catch (\Error) {
            if ($property->getValue($reflection) !== $this) {
                throw new ReflectionException();
            }
        }
    }
}
