<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use Typhoon\Reflection\AnonymousClassName;
use Typhoon\Reflection\ChangeDetector\FileChangeDetector;
use Typhoon\Reflection\ClassLoader;
use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\NameContext\NameContext;
use Typhoon\Reflection\NameContext\NameContextVisitor;
use Typhoon\Reflection\ParsingContext;
use Typhoon\Reflection\PhpDocParser\PhpDocParser;
use Typhoon\Reflection\PhpParser\PhpParser;
use Typhoon\Reflection\ReflectionException;
use Typhoon\Reflection\Reflector\Cache\ClassReflectorSetter;
use Typhoon\Reflection\Reflector\Cache\NullReflectionCache;
use Typhoon\Reflection\TypeContext\ClassExistenceChecker;
use Typhoon\Reflection\TypeContext\TypeContext;
use function Typhoon\Reflection\Exceptionally\exceptionally;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class Context implements ParsingContext, ClassReflector, ClassExistenceChecker
{
    /**
     * @var array<non-empty-string, bool>
     */
    private array $parsedFiles = [];

    /**
     * @var array<class-string<RootReflection>, array<non-empty-string, false|RootReflection|callable(ClassReflector): RootReflection>> false is used during parsing
     */
    private array $reflections = [];

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

        $code = exceptionally(static fn(): string|false => file_get_contents($file));
        $resource = new Resource(
            file: $file,
            extension: $extension,
            changeDetector: FileChangeDetector::fromContents($file, $code),
        );

        $nameContext = new NameContext();
        $this->phpParser->parseAndTraverse($code, [
            new PhpDocParsingVisitor($this->phpDocParser),
            new NameContextVisitor($nameContext),
            new DiscoveringVisitor(
                parsingContext: $this,
                typeContext: new TypeContext($nameContext, $this),
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

        /** @var non-empty-string $name */
        return $this->classLoader->loadClass($this, $name)
            && isset($this->reflections[ClassReflection::class][$name])
            && $this->reflections[ClassReflection::class][$name] !== false;
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
        });
    }

    public function __serialize(): array
    {
        throw new ReflectionException();
    }

    /**
     * @template TReflection of RootReflection
     * @param class-string<TReflection> $class
     * @param non-empty-string $name
     * @param \Closure(): void $parse
     * @return TReflection
     */
    private function resolveReflection(string $class, string $name, \Closure $parse): RootReflection
    {
        $reflection = $this->resolveMemoizedReflection($class, $name);

        if ($reflection === false) {
            throw new ReflectionException('Loop');
        }

        if ($reflection !== null) {
            return $reflection;
        }

        $cachedReflection = $this->cache->getReflection($class, $name);

        if ($cachedReflection !== null) {
            ClassReflectorSetter::set($cachedReflection, $this);

            /** @var TReflection */
            return $this->reflections[$class][$name] = $cachedReflection;
        }

        $this->reflections[$class][$name] = false;

        $parse();

        $reflection = $this->resolveMemoizedReflection($class, $name) ?? false;

        if ($reflection === false) {
            throw new ReflectionException('Not found');
        }

        return $reflection;
    }

    /**
     * @template TReflection of RootReflection
     * @param class-string<TReflection> $class
     * @param non-empty-string $name
     * @return null|false|TReflection
     */
    private function resolveMemoizedReflection(string $class, string $name): null|false|RootReflection
    {
        if (!isset($this->reflections[$class][$name])) {
            return null;
        }

        $reflection = $this->reflections[$class][$name];

        if ($reflection instanceof $class) {
            return $reflection;
        }

        if (\is_callable($reflection)) {
            /** @var TReflection */
            $reflection = $reflection($this);
            $this->cache->addReflection($reflection);

            return $this->reflections[$class][$name] = $reflection;
        }

        return false;
    }

    private function __clone() {}
}
