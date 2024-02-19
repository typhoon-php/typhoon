<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PhpParser\Parser as PhpParser;
use PhpParser\ParserFactory;
use Psr\SimpleCache\CacheInterface;
use Typhoon\Reflection\ClassLocator\ClassLocatorChain;
use Typhoon\Reflection\ClassLocator\ComposerClassLocator;
use Typhoon\Reflection\ClassLocator\NativeReflectionFileLocator;
use Typhoon\Reflection\ClassLocator\NativeReflectionLocator;
use Typhoon\Reflection\ClassLocator\PhpStormStubsClassLocator;
use Typhoon\Reflection\ClassReflection\ClassReflector;
use Typhoon\Reflection\Exception\ClassDoesNotExistException;
use Typhoon\Reflection\Metadata\ClassMetadata;
use Typhoon\Reflection\Metadata\MetadataCollection;
use Typhoon\Reflection\Metadata\RootMetadata;
use Typhoon\Reflection\NameContext\AnonymousClassName;
use Typhoon\Reflection\NativeReflector\NativeReflector;
use Typhoon\Reflection\PhpDocParser\PhpDocParser;
use Typhoon\Reflection\PhpDocParser\PHPStanOverPsalmOverOthersTagPrioritizer;
use Typhoon\Reflection\PhpDocParser\TagPrioritizer;
use Typhoon\Reflection\PhpParserReflector\PhpParserReflector;
use Typhoon\Reflection\TypeContext\ClassExistenceChecker;

/**
 * @api
 */
final class TyphoonReflector implements ClassExistenceChecker, ClassReflector
{
    private function __construct(
        private readonly PhpParserReflector $phpParserReflector,
        private readonly NativeReflector $nativeReflector,
        private readonly ClassLocator $classLocator,
        private readonly ?CacheInterface $cache,
    ) {}

    /**
     * @param ?array<ClassLocator> $classLocators
     */
    public static function build(
        ?CacheInterface $cache = null,
        ?array $classLocators = null,
        TagPrioritizer $tagPrioritizer = new PHPStanOverPsalmOverOthersTagPrioritizer(),
        ?PhpParser $phpParser = null,
    ): self {
        return new self(
            phpParserReflector: new PhpParserReflector(
                phpParser: $phpParser ?? (new ParserFactory())->createForNewestSupportedVersion(),
                phpDocParser: new PhpDocParser($tagPrioritizer),
            ),
            nativeReflector: new NativeReflector(),
            classLocator: new ClassLocatorChain($classLocators ?? self::defaultClassLocators()),
            cache: $cache,
        );
    }

    /**
     * @return non-empty-list<ClassLocator>
     */
    public static function defaultClassLocators(): array
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

        return $classLocators;
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

        $key = $this->cacheKey(ClassMetadata::class, $name);

        /** @psalm-suppress MixedAssignment */
        $cachedMetadata = $this->cache?->get($key);

        if ($cachedMetadata instanceof ClassMetadata) {
            return $cachedMetadata;
        }

        $location = $this->classLocator->locateClass($name);

        if ($location === null) {
            throw new ClassDoesNotExistException(sprintf('Class "%s" does not exist', ReflectionException::normalizeClass($name)));
        }

        if ($location instanceof \ReflectionClass) {
            $metadata = $this->nativeReflector->reflectClass($location);
            $this->cache?->set($key, $metadata);

            return $metadata;
        }

        return $this->reflectFile($location)->get(ClassMetadata::class, $name)
            ?? throw new ClassDoesNotExistException(sprintf('Class "%s" does not exist', ReflectionException::normalizeClass($name)));
    }

    private function reflectFile(FileResource $file): MetadataCollection
    {
        $collection = $this->phpParserReflector->reflectFile($file, $this);

        if ($this->cache === null) {
            return $collection;
        }

        $cacheItems = [];

        foreach ($collection as $item) {
            $cacheItems[$this->cacheKey($item::class, $item->name)] = $item;
        }

        $this->cache->setMultiple($cacheItems);

        return $collection;
    }

    /**
     * @param class-string<RootMetadata> $class
     * @param non-empty-string $name
     * @return non-empty-string
     */
    private function cacheKey(string $class, string $name): string
    {
        return hash('xxh128', $class . '#' . $name);
    }
}
