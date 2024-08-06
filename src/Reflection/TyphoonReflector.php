<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use PhpParser\Parser;
use PhpParser\ParserFactory;
use Psr\SimpleCache\CacheInterface;
use Typhoon\DeclarationId\AliasId;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\ClassConstantId;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\Internal\IdMap;
use Typhoon\DeclarationId\MethodId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\DeclarationId\ParameterId;
use Typhoon\DeclarationId\PropertyId;
use Typhoon\DeclarationId\TemplateId;
use Typhoon\PhpStormReflectionStubs\PhpStormStubsLocator;
use Typhoon\Reflection\Annotated\CustomTypeResolver;
use Typhoon\Reflection\Annotated\CustomTypeResolvers;
use Typhoon\Reflection\Exception\DeclarationNotFound;
use Typhoon\Reflection\Internal\Cache\Cache;
use Typhoon\Reflection\Internal\Cache\InMemoryPsr16Cache;
use Typhoon\Reflection\Internal\CompleteReflection\CleanUpInternallyDefined;
use Typhoon\Reflection\Internal\CompleteReflection\CompleteEnum;
use Typhoon\Reflection\Internal\CompleteReflection\CopyPromotedParameterToProperty;
use Typhoon\Reflection\Internal\CompleteReflection\RemoveCode;
use Typhoon\Reflection\Internal\CompleteReflection\RemoveContext;
use Typhoon\Reflection\Internal\CompleteReflection\SetAttributeRepeated;
use Typhoon\Reflection\Internal\CompleteReflection\SetClassCloneable;
use Typhoon\Reflection\Internal\CompleteReflection\SetInterfaceMethodAbstract;
use Typhoon\Reflection\Internal\CompleteReflection\SetParameterIndex;
use Typhoon\Reflection\Internal\CompleteReflection\SetParameterOptional;
use Typhoon\Reflection\Internal\CompleteReflection\SetReadonlyClassPropertyReadonly;
use Typhoon\Reflection\Internal\CompleteReflection\SetStringableInterface;
use Typhoon\Reflection\Internal\CompleteReflection\SetTemplateIndex;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Hook\Hooks;
use Typhoon\Reflection\Internal\Inheritance\ResolveClassInheritance;
use Typhoon\Reflection\Internal\Misc\NonSerializable;
use Typhoon\Reflection\Internal\NativeReflector\NativeReflector;
use Typhoon\Reflection\Internal\PhpDoc\PhpDocReflector;
use Typhoon\Reflection\Internal\PhpParser\CodeReflector;
use Typhoon\Reflection\Internal\PhpParser\NodeReflector;
use Typhoon\Reflection\Locator\AnonymousLocator;
use Typhoon\Reflection\Locator\ComposerLocator;
use Typhoon\Reflection\Locator\ConstantLocator;
use Typhoon\Reflection\Locator\FileAnonymousLocator;
use Typhoon\Reflection\Locator\Locators;
use Typhoon\Reflection\Locator\NamedClassLocator;
use Typhoon\Reflection\Locator\NamedFunctionLocator;
use Typhoon\Reflection\Locator\NativeReflectionClassLocator;
use Typhoon\Reflection\Locator\NativeReflectionFunctionLocator;
use Typhoon\Reflection\Locator\NoSymfonyPolyfillLocator;
use Typhoon\Reflection\Locator\OnlyLoadedClassLocator;
use Typhoon\Reflection\Locator\Resource;
use Typhoon\Reflection\Locator\ScannedResourceLocator;
use Typhoon\TypedMap\TypedMap;

/**
 * @api
 */
final class TyphoonReflector
{
    use NonSerializable;
    private const BUFFER_SIZE = 300;

    /**
     * @param ?iterable<ConstantLocator|NamedFunctionLocator|NamedClassLocator|AnonymousLocator> $locators
     * @param iterable<CustomTypeResolver> $customTypeResolvers
     */
    public static function build(
        ?iterable $locators = null,
        ?CacheInterface $cache = null,
        iterable $customTypeResolvers = [],
        ?Parser $phpParser = null,
    ): self {
        $phpDocReflector = new PhpDocReflector(new CustomTypeResolvers($customTypeResolvers));

        return new self(
            codeReflector: new CodeReflector(
                phpParser: $phpParser ?? (new ParserFactory())->createForHostVersion(),
                annotatedDeclarationsDiscoverer: $phpDocReflector,
                nodeReflector: new NodeReflector(),
            ),
            locators: new Locators($locators ?? self::defaultLocators()),
            hooks: new Hooks([
                $phpDocReflector,
                CopyPromotedParameterToProperty::Instance,
                CompleteEnum::Instance,
                SetStringableInterface::Instance,
                SetInterfaceMethodAbstract::Instance,
                SetClassCloneable::Instance,
                SetReadonlyClassPropertyReadonly::Instance,
                SetAttributeRepeated::Instance,
                SetParameterIndex::Instance,
                SetParameterOptional::Instance,
                SetTemplateIndex::Instance,
                ResolveClassInheritance::Instance,
                RemoveContext::Instance,
                RemoveCode::Instance,
                CleanUpInternallyDefined::Instance,
            ]),
            cache: new Cache($cache ?? self::defaultInMemoryCache()),
        );
    }

    /**
     * @return list<ConstantLocator|NamedFunctionLocator|NamedClassLocator|AnonymousLocator>
     */
    public static function defaultLocators(): array
    {
        $locators = [];

        if (class_exists(PhpStormStubsLocator::class)) {
            $locators[] = new PhpStormStubsLocator();
        }

        $locators[] = new OnlyLoadedClassLocator(new NativeReflectionClassLocator());
        $locators[] = new NativeReflectionFunctionLocator();
        $locators[] = new FileAnonymousLocator();

        if (ComposerLocator::isSupported()) {
            $locators[] = new NoSymfonyPolyfillLocator(new ComposerLocator());
        }

        return $locators;
    }

    public static function defaultInMemoryCache(): CacheInterface
    {
        return new InMemoryPsr16Cache();
    }

    /**
     * @param IdMap<ConstantId|NamedFunctionId|NamedClassId|AnonymousClassId, \Closure(self): TypedMap> $buffer
     */
    private function __construct(
        private readonly CodeReflector $codeReflector,
        private readonly Locators $locators,
        private readonly Hooks $hooks,
        private readonly Cache $cache,
        private readonly NativeReflector $nativeReflector = new NativeReflector(),
        private IdMap $buffer = new IdMap(),
    ) {}

    /**
     * @param non-empty-string $name
     */
    public function reflectConstant(string $name): ConstantReflection
    {
        return $this->reflect(Id::constant($name));
    }

    /**
     * @param non-empty-string $name
     * @psalm-assert-if-true callable-string $name
     */
    public function functionExists(string $name): bool
    {
        try {
            $this->reflectFunction($name);

            return true;
        } catch (DeclarationNotFound) {
            return false;
        }
    }

    /**
     * @template T of object
     * @param non-empty-string $name
     * @throws DeclarationNotFound
     */
    public function reflectFunction(string $name): FunctionReflection
    {
        return $this->reflect(Id::namedFunction($name));
    }

    /**
     * @param non-empty-string $class
     * @psalm-assert-if-true class-string $class
     */
    public function classExists(string $class): bool
    {
        try {
            $this->reflectClass($class);

            return true;
        } catch (DeclarationNotFound) {
            return false;
        }
    }

    /**
     * @template TObject of object
     * @param non-empty-string|class-string<TObject> $name
     * @return ($name is class-string<TObject>
     *     ? ClassReflection<TObject, NamedClassId<class-string<TObject>>|AnonymousClassId<class-string<TObject>>>
     *     : ClassReflection<object, NamedClassId<class-string>|AnonymousClassId<?class-string>>)
     * @throws DeclarationNotFound
     */
    public function reflectClass(string $name): ClassReflection
    {
        return $this->reflect(Id::class($name));
    }

    /**
     * @param non-empty-string $file
     * @param positive-int $line
     * @param ?positive-int $column
     * @return ClassReflection<object, AnonymousClassId<null>>
     * @throws DeclarationNotFound
     */
    public function reflectAnonymousClass(string $file, int $line, ?int $column = null): ClassReflection
    {
        /** @var ClassReflection<object, AnonymousClassId<null>> */
        return $this->reflect(Id::anonymousClass($file, $line, $column));
    }

    /**
     * @psalm-suppress InvalidReturnType, InvalidReturnStatement
     * @return (
     *     $id is ConstantId ? ConstantReflection :
     *     $id is NamedFunctionId ? FunctionReflection :
     *     $id is NamedClassId ? ClassReflection<object, NamedClassId<class-string>> :
     *     $id is AnonymousClassId<null> ? ClassReflection<object, AnonymousClassId<null>> :
     *     $id is AnonymousClassId<class-string> ? ClassReflection<object, AnonymousClassId<class-string>> :
     *     $id is ClassConstantId ? ClassConstantReflection :
     *     $id is PropertyId ? PropertyReflection :
     *     $id is MethodId ? MethodReflection :
     *     $id is ParameterId ? ParameterReflection :
     *     $id is AliasId ? AliasReflection :
     *     $id is TemplateId ? TemplateReflection :
     *     never
     * )
     * @throws DeclarationNotFound
     */
    public function reflect(Id $id): ConstantReflection|FunctionReflection|ClassReflection|ClassConstantReflection|PropertyReflection|MethodReflection|ParameterReflection|AliasReflection|TemplateReflection
    {
        try {
            if ($id instanceof NamedClassId || $id instanceof AnonymousClassId) {
                /** @var NamedClassId<class-string>|AnonymousClassId<?class-string> $id */
                return new ClassReflection($id, $this->reflectClassData($id), $this);
            }

            if ($id instanceof NamedFunctionId) {
                return new FunctionReflection($id, $this->reflectFunctionData($id), $this);
            }

            if ($id instanceof ConstantId) {
                return new ConstantReflection($id, $this->reflectConstantData($id), $this);
            }
        } finally {
            if ($this->buffer->count() > self::BUFFER_SIZE) {
                $this->buffer = $this->buffer->slice(-self::BUFFER_SIZE);
            }
        }

        return match (true) {
            $id instanceof PropertyId => $this->reflect($id->class)->properties()[$id->name],
            $id instanceof ClassConstantId => $this->reflect($id->class)->constants()[$id->name],
            $id instanceof MethodId => $this->reflect($id->class)->methods()[$id->name],
            $id instanceof ParameterId => $this->reflect($id->function)->parameters()[$id->name],
            $id instanceof AliasId => $this->reflect($id->class)->aliases()[$id->name],
            $id instanceof TemplateId => $this->reflect($id->declaration)->templates()[$id->name],
            default => throw new \LogicException($id->describe() . ' is not supported yet'),
        };
    }

    public function withResource(Resource $resource): self
    {
        $reflectedResource = $this->reflectResource($resource);

        return new self(
            codeReflector: $this->codeReflector,
            locators: $this->locators->with(new ScannedResourceLocator($reflectedResource->ids(), $resource)),
            hooks: $this->hooks,
            cache: $this->cache,
            nativeReflector: $this->nativeReflector,
            buffer: $this->buffer->withMap($reflectedResource),
        );
    }

    private function reflectConstantData(ConstantId $id): TypedMap
    {
        $buffered = $this->buffer[$id] ?? null;

        if ($buffered !== null) {
            return $buffered($this);
        }

        $cachedData = $this->cache->get($id);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $resource = $this->locators->locate($id);

        if ($resource !== null) {
            $this->buffer = $this->buffer->withMap($this->reflectResource($resource));

            return ($this->buffer[$id] ?? throw new DeclarationNotFound($id))($this);
        }

        $nativeData = $this->nativeReflector->reflectConstant($id);

        if ($nativeData !== null) {
            $this->cache->set($id, $nativeData);

            return $nativeData;
        }

        throw new DeclarationNotFound($id);
    }

    private function reflectFunctionData(NamedFunctionId $id): TypedMap
    {
        $buffered = $this->buffer[$id] ?? null;

        if ($buffered !== null) {
            return $buffered($this);
        }

        $cachedData = $this->cache->get($id);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $resource = $this->locators->locate($id);

        if ($resource !== null) {
            $this->buffer = $this->buffer->withMap($this->reflectResource($resource));

            return ($this->buffer[$id] ?? throw new DeclarationNotFound($id))($this);
        }

        $nativeData = $this->nativeReflector->reflectNamedFunction($id);

        if ($nativeData !== null) {
            $this->cache->set($id, $nativeData);

            return $nativeData;
        }

        throw new DeclarationNotFound($id);
    }

    private function reflectClassData(NamedClassId|AnonymousClassId $id): TypedMap
    {
        $buffered = $this->buffer[$id] ?? null;

        if ($buffered !== null) {
            return $buffered($this);
        }

        $cachedData = $this->cache->get($id);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $resource = $this->locators->locate($id);

        if ($resource !== null) {
            $this->buffer = $this->buffer->withMap($this->reflectResource($resource));

            return ($this->buffer[$id] ?? throw new DeclarationNotFound($id))($this);
        }

        if ($id instanceof NamedClassId) {
            $nativeData = $this->nativeReflector->reflectNamedClass($id);

            if ($nativeData !== null) {
                $this->cache->set($id, $nativeData);

                return $nativeData;
            }
        }

        throw new DeclarationNotFound($id);
    }

    /**
     * @return IdMap<ConstantId|NamedFunctionId|NamedClassId|AnonymousClassId, \Closure(self): TypedMap>
     */
    private function reflectResource(Resource $resource): IdMap
    {
        $code = $resource->data[Data::Code];
        $file = $resource->data[Data::File];

        $baseData = $resource->data;
        $hooks = $this->hooks->merge($resource->hooks);

        $idReflectors = $this->codeReflector->reflectCode($code, $file)->map(
            static fn(\Closure $idReflector, ConstantId|NamedFunctionId|NamedClassId|AnonymousClassId $id): \Closure =>
                static function (self $reflector) use ($id, $idReflector, $baseData, $hooks): TypedMap {
                    static $started = false;

                    if ($started) {
                        throw new \LogicException(\sprintf('Infinite recursive reflection of %s detected', $id->describe()));
                    }

                    $started = true;

                    $data = $baseData->withMap($idReflector());
                    $data = $hooks->process($id, $data, $reflector);

                    $reflector->cache->set($id, $data);
                    $reflector->buffer = $reflector->buffer->without($id);

                    return $data;
                },
        );

        return $idReflectors->withMap(self::reflectAnonymousClassesWithoutColumn($idReflectors->ids()));
    }

    /**
     * @param list<Id> $ids
     * @return IdMap<AnonymousClassId, \Closure(self): TypedMap>
     */
    private static function reflectAnonymousClassesWithoutColumn(array $ids): IdMap
    {
        return new IdMap((static function () use ($ids): \Generator {
            $lineToIds = [];

            foreach ($ids as $id) {
                if ($id instanceof AnonymousClassId) {
                    $lineToIds[$id->line][] = $id;
                }
            }

            foreach ($lineToIds as $idsOnLine) {
                $idWithoutColumn = $idsOnLine[0]->withoutColumn();

                if (\count($idsOnLine) === 1) {
                    yield $idWithoutColumn => static fn(self $reflector): TypedMap => $reflector->reflectClassData($idsOnLine[0]);

                    continue;
                }

                yield $idWithoutColumn => static function () use ($idWithoutColumn, $idsOnLine): never {
                    throw new \RuntimeException(\sprintf(
                        'Cannot reflect %s, because %d anonymous classes are declared at columns %s. ' .
                        'Use TyphoonReflector::reflectAnonymousClass() with a $column argument to reflect the exact class you need',
                        $idWithoutColumn->describe(),
                        \count($idsOnLine),
                        implode(', ', array_column($idsOnLine, 'column')),
                    ));
                };
            }
        })());
    }
}
