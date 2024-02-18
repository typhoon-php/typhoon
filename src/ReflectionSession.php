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
use Typhoon\Reflection\TypeContext\WeakClassExistenceChecker;

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

        if ($this->cache?->get(ClassMetadata::class, $name) !== null) {
            return true;
        }

        $resource = $this->classLocator->locateClass($name);

        if ($resource instanceof FileResource) {
            $this->reflectFile($resource);

            return $this->metadata->has(ClassMetadata::class, $name);
        }

        if ($resource instanceof \ReflectionClass) {
            $nativeReflector = $this->nativeReflector;
            $this->metadata->setFactory(ClassMetadata::class, $name, static fn(): ClassMetadata => $nativeReflector->reflectClass($resource));

            return true;
        }

        return false;
    }

    /**
     * @psalm-suppress InvalidReturnType, InvalidReturnStatement
     */
    public function reflectClass(string|object $nameOrObject): ClassReflection
    {
        if (\is_object($nameOrObject)) {
            return new ClassReflection($this, $this->reflectClassMetadata($nameOrObject::class));
        }

        if ($nameOrObject === '') {
            throw new ClassDoesNotExistException('Class "" does not exist');
        }

        return new ClassReflection($this, $this->reflectClassMetadata($nameOrObject));
    }

    public function flush(): void
    {
        $this->cache?->setMultiple($this->metadata);
        $this->metadata->clear();
        $this->reflectedResources = [];
    }

    /**
     * @param non-empty-string $name
     * @throws ReflectionException
     */
    private function reflectClassMetadata(string $name): ClassMetadata
    {
        $anonymousName = AnonymousClassName::tryFromString($name);

        if ($anonymousName !== null) {
            return $this->phpParserReflector->reflectAnonymousClass($anonymousName);
        }

        $metadata = $this->metadata->get(ClassMetadata::class, $name);

        if ($metadata !== null) {
            return $metadata;
        }

        $metadata = $this->cache?->get(ClassMetadata::class, $name);

        if ($metadata !== null) {
            return $metadata;
        }

        $resource = $this->classLocator->locateClass($name);

        if ($resource instanceof FileResource) {
            $this->reflectFile($resource);
            $metadata = $this->metadata->get(ClassMetadata::class, $name);

            if ($metadata !== null) {
                return $metadata;
            }

            throw new ClassDoesNotExistException(sprintf('Class "%s" does not exist', ReflectionException::normalizeClass($name)));
        }

        if ($resource instanceof \ReflectionClass) {
            $metadata = $this->nativeReflector->reflectClass($resource);
            $this->metadata->set($metadata);

            return $metadata;
        }

        throw new ClassDoesNotExistException(sprintf('Class "%s" does not exist', ReflectionException::normalizeClass($name)));
    }

    private function reflectFile(FileResource $file): void
    {
        if (!isset($this->reflectedResources[$file->file])) {
            $this->phpParserReflector->reflectFile($file, $this->metadata, new WeakClassExistenceChecker($this));
            $this->reflectedResources[$file->file] = true;
        }
    }
}
