<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\ClassReflection\ClassReflector;
use Typhoon\Reflection\Exception\ClassDoesNotExistException;
use Typhoon\Reflection\Metadata\ClassMetadata;
use Typhoon\Reflection\Metadata\MetadataCache;
use Typhoon\Reflection\Metadata\MetadataLazyCollection;
use Typhoon\Reflection\NameContext\AnonymousClassName;
use Typhoon\Reflection\NativeReflector\NativeReflector;
use Typhoon\Reflection\PhpParserReflector\PhpParserReflector;
use Typhoon\Reflection\TypeContext\ClassExistenceChecker;

/**
 * @api
 */
final class ReflectionSession implements ClassExistenceChecker, ClassReflector
{
    private readonly MetadataLazyCollection $metadata;

    /**
     * @var array<non-empty-string, true>
     */
    private array $reflectedResources = [];

    /**
     * @var array<non-empty-string, false|ClassReflection>
     */
    private array $reflectedClasses = [];

    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     */
    public function __construct(
        private readonly PhpParserReflector $phpParserReflector,
        private readonly NativeReflector $nativeReflector,
        private readonly ClassLocator $classLocator,
        private readonly ?MetadataCache $cache,
    ) {
        $this->metadata = new MetadataLazyCollection();
    }

    /**
     * @psalm-assert-if-true class-string $name
     */
    public function classExists(string $name): bool
    {
        if ($name === '') {
            return false;
        }

        if (isset($this->reflectedClasses[$name])) {
            return $this->reflectedClasses[$name] !== false;
        }

        if (class_exists($name, false) || interface_exists($name, false) || trait_exists($name, false)) {
            return true;
        }

        if (str_contains($name, '@')) {
            return false;
        }

        /** @var non-empty-string $name */
        if ($this->metadata->has(ClassMetadata::class, $name)) {
            return true;
        }

        $metadata = $this->cache?->get(ClassMetadata::class, $name);

        if ($metadata !== null) {
            $this->reflectedClasses[$name] = new ClassReflection($this, $metadata);

            return true;
        }

        $resource = $this->classLocator->locateClass($name);

        if ($resource instanceof FileResource) {
            $this->reflectFile($resource);

            if ($this->metadata->has(ClassMetadata::class, $name)) {
                return true;
            }

            return $this->reflectedClasses[$name] = false;
        }

        if ($resource instanceof \ReflectionClass) {
            $this->metadata->set(ClassMetadata::class, $name, fn(): ClassMetadata => $this->nativeReflector->reflectClass($resource));

            return true;
        }

        return $this->reflectedClasses[$name] = false;
    }

    /**
     * @psalm-suppress InvalidReturnType, InvalidReturnStatement
     */
    public function reflectClass(string|object $nameOrObject): ClassReflection
    {
        if (\is_object($nameOrObject)) {
            $name = $nameOrObject::class;
        } else {
            if ($nameOrObject === '') {
                throw new ClassDoesNotExistException('Class "" does not exist');
            }

            $name = $nameOrObject;
        }

        if (isset($this->reflectedClasses[$name])) {
            if ($this->reflectedClasses[$name] === false) {
                throw new ClassDoesNotExistException(sprintf('Class "%s" does not exist', ReflectionException::normalizeClass($name)));
            }

            return $this->reflectedClasses[$name];
        }

        $anonymousName = AnonymousClassName::tryFromString($name);

        if ($anonymousName !== null) {
            $metadata = $this->phpParserReflector->reflectAnonymousClass($anonymousName);

            return $this->reflectedClasses[$name] = new ClassReflection($this, $metadata);
        }

        $metadata = $this->metadata->get(ClassMetadata::class, $name);

        if ($metadata !== null) {
            return $this->reflectedClasses[$name] = new ClassReflection($this, $metadata);
        }

        $metadata = $this->cache?->get(ClassMetadata::class, $name);

        if ($metadata !== null) {
            return $this->reflectedClasses[$name] = new ClassReflection($this, $metadata);
        }

        $resource = $this->classLocator->locateClass($name);

        if ($resource instanceof FileResource) {
            $this->reflectFile($resource);
            $metadata = $this->metadata->get(ClassMetadata::class, $name);

            if ($metadata !== null) {
                return $this->reflectedClasses[$name] = new ClassReflection($this, $metadata);
            }

            $this->reflectedClasses[$name] = false;

            throw new ClassDoesNotExistException(sprintf('Class "%s" does not exist', ReflectionException::normalizeClass($name)));
        }

        if ($resource instanceof \ReflectionClass) {
            return $this->reflectedClasses[$name] = new ClassReflection($this, $this->nativeReflector->reflectClass($resource));
        }

        $this->reflectedClasses[$name] = false;

        throw new ClassDoesNotExistException(sprintf('Class "%s" does not exist', ReflectionException::normalizeClass($name)));
    }

    public function flush(): void
    {
        $this->cache?->setMultiple($this->metadata);
        $this->metadata->clear();
        $this->reflectedResources = [];
        $this->reflectedClasses = [];
    }

    private function reflectFile(FileResource $file): void
    {
        if (!isset($this->reflectedResources[$file->file])) {
            $this->phpParserReflector->reflectFile($file, $this->metadata, $this);
            $this->reflectedResources[$file->file] = true;
        }
    }
}
