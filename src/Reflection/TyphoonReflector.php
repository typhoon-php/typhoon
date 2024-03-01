<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PhpParser\Parser as PhpParser;
use PhpParser\ParserFactory;
use Psr\SimpleCache\CacheInterface;
use Typhoon\Reflection\Cache\InMemoryCache;
use Typhoon\Reflection\ClassLocator\ClassLocators;
use Typhoon\Reflection\ClassLocator\ComposerClassLocator;
use Typhoon\Reflection\ClassLocator\NativeReflectionFileLocator;
use Typhoon\Reflection\ClassLocator\NativeReflectionLocator;
use Typhoon\Reflection\ClassLocator\PhpStormStubsClassLocator;
use Typhoon\Reflection\ClassReflection\ClassReflector;
use Typhoon\Reflection\Exception\ClassDoesNotExistException;
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
    ) {}

    public static function build(
        ?ClassLocator $classLocator = null,
        CacheInterface $cache = new InMemoryCache(),
        TagPrioritizer $tagPrioritizer = new PrefixBasedTagPrioritizer(),
        ?PhpParser $phpParser = null,
    ): self {
        return new self(
            phpParserReflector: new PhpParserReflector(
                phpParser: $phpParser ?? (new ParserFactory())->createForNewestSupportedVersion(),
                phpDocParser: new PhpDocParser($tagPrioritizer),
            ),
            nativeReflector: new NativeReflector(),
            classLocator: $classLocator ?? self::defaultClassLocator(),
            metadataStorage: new MetadataStorage($cache),
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

        $classLocators[] = new NativeReflectionFileLocator();
        $classLocators[] = new NativeReflectionLocator();

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
        } catch (ClassDoesNotExistException) {
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
            throw new ClassDoesNotExistException('Class "" does not exist');
        }

        return new ClassReflection($this, $this->reflectClassMetadata($name));
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

        $location = $this->classLocator->locateClass($name)
            ?? throw new ClassDoesNotExistException(sprintf('Class "%s" does not exist', ReflectionException::normalizeClass($name)));

        if ($location instanceof \ReflectionClass) {
            $metadata = $this->nativeReflector->reflectClass($location);
            $this->metadataStorage->save($metadata);

            return $metadata;
        }

        $this->phpParserReflector->reflectFile($location, new WeakClassExistenceChecker($this), $this->metadataStorage);
        $metadata = $this->metadataStorage->get(ClassMetadata::class, $name);
        $this->metadataStorage->commit();

        return $metadata ?? throw new ClassDoesNotExistException(sprintf('Class "%s" does not exist', ReflectionException::normalizeClass($name)));
    }
}
