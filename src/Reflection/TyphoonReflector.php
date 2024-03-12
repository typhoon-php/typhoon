<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PhpParser\Parser as PhpParser;
use PhpParser\ParserFactory;
use Psr\SimpleCache\CacheInterface;
use Typhoon\Reflection\Cache\InMemoryCache;
use Typhoon\Reflection\ClassLocator\ClassLocators;
use Typhoon\Reflection\ClassLocator\ComposerClassLocator;
use Typhoon\Reflection\ClassLocator\PhpStormStubsClassLocator;
use Typhoon\Reflection\ClassReflection\ClassReflector;
use Typhoon\Reflection\Exception\ClassDoesNotExist;
use Typhoon\Reflection\Metadata\ClassMetadata;
use Typhoon\Reflection\Metadata\MetadataStorage;
use Typhoon\Reflection\NameContext\AnonymousClassName;
use Typhoon\Reflection\NativeReflector\NativeReflector;
use Typhoon\Reflection\PhpDocParser\PhpDocParser;
use Typhoon\Reflection\PhpDocParser\PrefixBasedTagPrioritizer;
use Typhoon\Reflection\PhpDocParser\TagPrioritizer;
use Typhoon\Reflection\PhpParserReflector\PhpParserReflector;
use Typhoon\Reflection\TypeContext\ClassExistenceChecker;
use Typhoon\Reflection\TypeContext\WeakClassExistenceChecker;
use Typhoon\Type\Type;
use Typhoon\Type\types;

/**
 * @api
 */
final class TyphoonReflector implements ClassExistenceChecker, ClassReflector
{
    private function __construct(
        private readonly PhpParserReflector $phpParserReflector,
        private readonly NativeReflector $nativeReflector,
        private readonly ClassLocator $classLocator,
        private readonly MetadataStorage $metadataStorage,
        private readonly bool $fallbackToNativeReflection,
    ) {}

    public static function build(
        ?ClassLocator $classLocator = null,
        CacheInterface $cache = new InMemoryCache(),
        TagPrioritizer $tagPrioritizer = new PrefixBasedTagPrioritizer(),
        ?PhpParser $phpParser = null,
        bool $fallbackToNativeReflection = true,
    ): self {
        return new self(
            phpParserReflector: new PhpParserReflector(
                phpParser: $phpParser ?? (new ParserFactory())->createForNewestSupportedVersion(),
                phpDocParser: new PhpDocParser($tagPrioritizer),
            ),
            nativeReflector: new NativeReflector(),
            classLocator: $classLocator ?? self::defaultClassLocator(),
            metadataStorage: new MetadataStorage($cache),
            fallbackToNativeReflection: $fallbackToNativeReflection,
        );
    }

    public static function defaultClassLocator(): ClassLocator
    {
        $classLocators = [];

        if (PhpStormStubsClassLocator::isSupported()) {
            $classLocators[] = new PhpStormStubsClassLocator();
        }

        if (ComposerClassLocator::isSupported()) {
            $classLocators[] = new ComposerClassLocator();
        }

        return new ClassLocators($classLocators);
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

        // If $name is a valid anonymous class name, it must have passed the class_exists() check above.
        if (str_contains($name, '@')) {
            return false;
        }

        /** @var non-empty-string $name Psalm */
        try {
            $this->reflectClassMetadata($name);

            return true;
        } catch (ClassDoesNotExist) {
            return false;
        }
    }

    /**
     * @psalm-suppress InvalidReturnType, InvalidReturnStatement
     * @template T of object
     * @param string|class-string<T>|T $name
     * @return ClassReflection<T>
     * @throws ReflectionException
     */
    public function reflectClass(string|object $name): ClassReflection
    {
        if (\is_object($name)) {
            return new ClassReflection($this, $this->reflectClassMetadata($name::class));
        }

        if ($name === '') {
            throw new ClassDoesNotExist($name);
        }

        return new ClassReflection($this, $this->reflectClassMetadata($name));
    }

    public function reflectValue(mixed $value): Type
    {
        if ($value === null) {
            return types::null;
        }

        if (\is_scalar($value)) {
            return types::literalValue($value);
        }

        if (\is_array($value)) {
            return types::arrayShape(array_map($this->reflectValue(...), $value));
        }

        if ($value instanceof \Closure) {
            // TODO reflect parameters and return type
            return types::closure;
        }

        if (\is_resource($value)) {
            return types::resource;
        }

        \assert(\is_object($value), 'Unexpected value types ' . get_debug_type($value));

        return types::object($value::class);
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

        $metadata = $this->metadataStorage->get(ClassMetadata::class, $name);

        if ($metadata !== null) {
            return $metadata;
        }

        $resource = $this->locateClass($name);

        if ($resource instanceof \ReflectionClass) {
            $metadata = $this->nativeReflector->reflectClass($resource);
            $this->metadataStorage->save($metadata);

            return $metadata;
        }

        $this->phpParserReflector->reflectFile($resource, new WeakClassExistenceChecker($this), $this->metadataStorage);
        $metadata = $this->metadataStorage->get(ClassMetadata::class, $name);
        $this->metadataStorage->commit();

        return $metadata ?? throw new ClassDoesNotExist($name);
    }

    /**
     * @param non-empty-string $name
     */
    private function locateClass(string $name): FileResource|\ReflectionClass
    {
        $resource = $this->classLocator->locateClass($name);

        if ($resource instanceof FileResource) {
            return $resource;
        }

        if ($resource instanceof \ReflectionClass) {
            trigger_deprecation(
                'typhoon/reflection',
                '0.3.1',
                'Returning %s from %s is deprecated, use %s::build($fallbackToNativeReflection) instead.',
                \ReflectionClass::class,
                ClassLocator::class,
                self::class,
            );

            return $resource;
        }

        if (!$this->fallbackToNativeReflection) {
            throw new ClassDoesNotExist($name);
        }

        try {
            $reflectionClass = new \ReflectionClass($name);
        } catch (\ReflectionException) {
            throw new ClassDoesNotExist($name);
        }

        $file = $reflectionClass->getFileName();

        if ($file !== false) {
            return new FileResource($file, $reflectionClass->getExtensionName());
        }

        return $reflectionClass;
    }
}
