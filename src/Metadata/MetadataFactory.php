<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Metadata;

use ExtendedTypeSystem\NameResolution\NameResolverFactory;
use ExtendedTypeSystem\Parser\PHPDocParser;
use PHPyh\LRUMemoizer\LRUMemoizer;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem
 */
final class MetadataFactory
{
    /**
     * @var \WeakMap<\Closure, FunctionMetadata>
     */
    private \WeakMap $closureMetadata;

    public function __construct(
        private readonly LRUMemoizer $memoizer = new LRUMemoizer(),
        private readonly PHPDocParser $phpDocParser = new PHPDocParser(),
        private readonly NameResolverFactory $nameResolverFactory = new NameResolverFactory(),
    ) {
        /** @var \WeakMap<\Closure, FunctionMetadata> */
        $this->closureMetadata = new \WeakMap();
    }

    /**
     * @param ?class-string $scopeClass
     */
    public function fromStringMetadata(string $phpDoc, ?string $scopeClass = null): FromStringMetadata
    {
        return $this->memoizer->get($phpDoc, fn (): FromStringMetadata => new FromStringMetadata(
            classMetadata: $scopeClass === null ? null : $this->classMetadata($scopeClass),
            phpDocTags: $this->phpDocParser->parsePhpDoc($phpDoc),
        ));
    }

    /**
     * @param callable-string|\Closure $function
     */
    public function functionMetadata(string|\Closure $function): FunctionMetadata
    {
        $metadataFactory = function () use ($function): FunctionMetadata {
            $reflectionFunction = new \ReflectionFunction($function);
            $scopeClass = $reflectionFunction->getClosureScopeClass()?->name;

            return new FunctionMetadata(
                function: \is_string($function) ? $function : null,
                scopeClass: $scopeClass === null ? null : $this->classMetadata($scopeClass),
                nameResolver: $this->nameResolverFactory->create(
                    file: $reflectionFunction->getFileName(),
                    namespace: $reflectionFunction->getNamespaceName(),
                ),
                phpDocTags: $this->phpDocParser->parsePhpDoc($reflectionFunction->getDocComment()),
            );
        };

        if (\is_string($function)) {
            return $this->memoizer->get($function, $metadataFactory);
        }

        return $this->closureMetadata[$function] ??= $metadataFactory();
    }

    /**
     * @param class-string $class
     */
    public function classMetadata(string $class): ClassMetadata
    {
        return $this->memoizer->get($class, function () use ($class): ClassMetadata {
            $reflectionClass = new \ReflectionClass($class);

            return new ClassMetadata(
                class: $reflectionClass->name,
                parent: ($reflectionClass->getParentClass() ?: null)?->name,
                final: $reflectionClass->isFinal(),
                nameResolver: $this->nameResolverFactory->create(
                    file: $reflectionClass->getFileName(),
                    namespace: $reflectionClass->getNamespaceName(),
                ),
                phpDocTags: $this->phpDocParser->parsePhpDoc($reflectionClass->getDocComment()),
            );
        });
    }

    /**
     * @param class-string $class
     */
    public function propertyMetadata(string $class, string $property): PropertyMetadata
    {
        $reflectionProperty = new \ReflectionProperty($class, $property);

        return $this->memoizer->get($reflectionProperty->class.'::$'.$property, function () use ($reflectionProperty): PropertyMetadata {
            return new PropertyMetadata(
                static: $reflectionProperty->isStatic(),
                promoted: $reflectionProperty->isPromoted(),
                class: $this->classMetadata($reflectionProperty->class),
                phpDocTags: $this->phpDocParser->parsePhpDoc($reflectionProperty->getDocComment()),
            );
        });
    }

    /**
     * @param class-string $class
     */
    public function methodMetadata(string $class, string $method): MethodMetadata
    {
        $reflectionMethod = new \ReflectionMethod($class, $method);

        return $this->memoizer->get($reflectionMethod->class.'::'.$method, function () use ($reflectionMethod): MethodMetadata {
            return new MethodMetadata(
                method: $reflectionMethod->getName(),
                static: $reflectionMethod->isStatic(),
                class: $this->classMetadata($reflectionMethod->class),
                phpDocTags: $this->phpDocParser->parsePhpDoc($reflectionMethod->getDocComment()),
            );
        });
    }
}
