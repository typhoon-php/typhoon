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
use Typhoon\Reflection\Metadata\MetadataCache;
use Typhoon\Reflection\NativeReflector\NativeReflector;
use Typhoon\Reflection\PhpDocParser\PhpDocParser;
use Typhoon\Reflection\PhpDocParser\PHPStanOverPsalmOverOthersTagPrioritizer;
use Typhoon\Reflection\PhpDocParser\TagPrioritizer;
use Typhoon\Reflection\PhpParserReflector\PhpParserReflector;

/**
 * @api
 */
final class TyphoonReflector
{
    private function __construct(
        private readonly MetadataCache $cache,
        private readonly PhpParserReflector $phpParserReflector,
        private readonly NativeReflector $nativeReflector,
        private readonly ClassLocator $classLocator,
    ) {}

    /**
     * @param ?array<ClassLocator> $classLocators
     */
    public static function build(
        ?CacheInterface $cache = null,
        bool $detectChanges = true,
        ?array $classLocators = null,
        TagPrioritizer $tagPrioritizer = new PHPStanOverPsalmOverOthersTagPrioritizer(),
        ?PhpParser $phpParser = null,
    ): self {
        return new self(
            cache: new MetadataCache($cache, $detectChanges),
            phpParserReflector: new PhpParserReflector(
                phpParser: $phpParser ?? (new ParserFactory())->createForNewestSupportedVersion(),
                phpDocParser: new PhpDocParser($tagPrioritizer),
            ),
            nativeReflector: new NativeReflector(),
            classLocator: new ClassLocatorChain($classLocators ?? self::defaultClassLocators()),
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

    public function startSession(): ReflectionSession
    {
        return new ReflectionSession(
            cache: $this->cache,
            phpParserReflector: $this->phpParserReflector,
            nativeReflector: $this->nativeReflector,
            classLocator: $this->classLocator,
        );
    }

    /**
     * @psalm-assert-if-true class-string $name
     */
    public function classExists(string $name): bool
    {
        return $this->startSession()->classExists($name);
    }

    /**
     * @template T of object
     * @param string|class-string<T>|T $nameOrObject
     * @return ClassReflection<T>
     */
    public function reflectClass(string|object $nameOrObject): ClassReflection
    {
        return $this->startSession()->reflectClass($nameOrObject);
    }
}
