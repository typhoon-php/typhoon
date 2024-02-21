<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\AttributeReflection\AttributeReflections;
use Typhoon\Reflection\ClassReflection\ClassReflector;
use Typhoon\Reflection\Exception\DefaultReflectionException;
use Typhoon\Reflection\Metadata\MethodMetadata;
use Typhoon\Reflection\Metadata\ParameterMetadata;
use Typhoon\Type\Type;

/**
 * @api
 * @property-read non-empty-string $name
 * @property-read class-string $class
 * @psalm-suppress PropertyNotSetInConstructor, MissingImmutableAnnotation
 */
final class MethodReflection extends \ReflectionMethod
{
    private ?AttributeReflections $attributes = null;

    private bool $nativeLoaded = false;

    /**
     * @var ?list<ParameterReflection>
     */
    private ?array $parameters = null;

    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     * @param class-string $currentClass
     */
    public function __construct(
        private readonly ClassReflector $classReflector,
        private readonly MethodMetadata $metadata,
        private readonly string $currentClass,
    ) {
        unset($this->name, $this->class);
    }

    /**
     * @psalm-suppress MethodSignatureMismatch, UnusedPsalmSuppress, UnusedParam
     */
    public static function createFromMethodName(string $method): static
    {
        throw new DefaultReflectionException('Not implemented.');
    }

    public function __get(string $name)
    {
        return match ($name) {
            'name' => $this->metadata->name,
            'class' => $this->metadata->class,
            default => new \OutOfBoundsException(sprintf('Property %s::$%s does not exist.', self::class, $name)),
        };
    }

    public function __isset(string $name): bool
    {
        return $name === 'name' || $name === 'class';
    }

    public function __toString(): string
    {
        $this->loadNative();

        return parent::__toString();
    }

    /**
     * @template TClass as object
     * @param class-string<TClass>|null $name
     * @return ($name is null ? list<AttributeReflection<object>> : list<AttributeReflection<TClass>>)
     */
    public function getAttributes(?string $name = null, int $flags = 0): array
    {
        if ($this->attributes === null) {
            $class = $this->metadata->class;
            $method = $this->metadata->name;
            $this->attributes = AttributeReflections::create(
                $this->classReflector,
                $this->metadata->attributes,
                static fn(): array => (new \ReflectionMethod($class, $method))->getAttributes(),
            );
        }

        return $this->attributes->get($name, $flags);
    }

    public function getClosure(?object $object = null): \Closure
    {
        $this->loadNative();

        return parent::getClosure($object);
    }

    public function getClosureCalledClass(): ?ClassReflection
    {
        return null;
    }

    public function getClosureScopeClass(): ?ClassReflection
    {
        return null;
    }

    public function getClosureThis(): ?object
    {
        return null;
    }

    public function getClosureUsedVariables(): array
    {
        return [];
    }

    public function getDeclaringClass(): ClassReflection
    {
        return $this->classReflector->reflectClass($this->metadata->class);
    }

    public function getDocComment(): string|false
    {
        return $this->metadata->docComment;
    }

    public function getEndLine(): int|false
    {
        return $this->metadata->endLine;
    }

    public function getExtension(): ?\ReflectionExtension
    {
        if ($this->metadata->extension === false) {
            return null;
        }

        return new \ReflectionExtension($this->metadata->extension);
    }

    public function getExtensionName(): string|false
    {
        return $this->metadata->extension;
    }

    public function getFileName(): string|false
    {
        return $this->metadata->file;
    }

    public function getModifiers(): int
    {
        return $this->metadata->modifiers;
    }

    public function getName(): string
    {
        return $this->metadata->name;
    }

    public function getNamespaceName(): string
    {
        return '';
    }

    public function getNumberOfParameters(): int
    {
        return \count($this->metadata->parameters);
    }

    public function getNumberOfRequiredParameters(): int
    {
        foreach ($this->metadata->parameters as $parameter) {
            if ($parameter->optional) {
                return $parameter->position;
            }
        }

        return $this->getNumberOfParameters();
    }

    public function getParameter(int|string $nameOrPosition): ParameterReflection
    {
        $parameters = $this->getParameters();

        if (\is_int($nameOrPosition)) {
            if (isset($parameters[$nameOrPosition])) {
                return $parameters[$nameOrPosition];
            }

            throw new DefaultReflectionException();
        }

        foreach ($parameters as $parameter) {
            if ($parameter->name === $nameOrPosition) {
                return $parameter;
            }
        }

        throw new DefaultReflectionException();
    }

    /**
     * @return list<ParameterReflection>
     */
    public function getParameters(): array
    {
        return $this->parameters ??= array_map(
            fn(ParameterMetadata $parameter): ParameterReflection => new ParameterReflection($this->classReflector, $parameter),
            $this->metadata->parameters,
        );
    }

    public function getPrototype(): \ReflectionMethod
    {
        if ($this->metadata->prototype === null) {
            throw new DefaultReflectionException(sprintf(
                'Method %s::%s does not have a prototype',
                ReflectionException::normalizeClass($this->currentClass),
                $this->metadata->name,
            ));
        }

        [$class, $name] = $this->metadata->prototype;

        return $this->classReflector->reflectClass($class)->getMethod($name);
    }

    public function getReturnType(): ?\ReflectionType
    {
        $this->loadNative();

        return parent::getReturnType();
    }

    public function getShortName(): string
    {
        return $this->metadata->name;
    }

    public function getStartLine(): int|false
    {
        return $this->metadata->startLine;
    }

    public function getStaticVariables(): array
    {
        $this->loadNative();

        return parent::getStaticVariables();
    }

    public function getTemplate(int|string $nameOrPosition): TemplateReflection
    {
        if (\is_int($nameOrPosition)) {
            return $this->metadata->templates[$nameOrPosition] ?? throw new DefaultReflectionException();
        }

        foreach ($this->metadata->templates as $template) {
            if ($template->name === $nameOrPosition) {
                return $template;
            }
        }

        throw new DefaultReflectionException();
    }

    /**
     * @return list<TemplateReflection>
     */
    public function getTemplates(): array
    {
        return $this->metadata->templates;
    }

    public function getTentativeReturnType(): ?\ReflectionType
    {
        $this->loadNative();

        return parent::getTentativeReturnType();
    }

    /**
     * @return ($origin is Origin::Resolved ? Type : null|Type)
     */
    public function getTyphoonReturnType(Origin $origin = Origin::Resolved): ?Type
    {
        return $this->metadata->returnType->get($origin);
    }

    public function getTyphoonThrowsType(): Type
    {
        return $this->metadata->throwsType;
    }

    public function hasPrototype(): bool
    {
        return $this->metadata->prototype !== null;
    }

    public function hasReturnType(): bool
    {
        if ($this->isInternal()) {
            $this->loadNative();

            return parent::hasReturnType();
        }

        return $this->metadata->returnType->native !== null;
    }

    public function hasTentativeReturnType(): bool
    {
        $this->loadNative();

        return parent::hasTentativeReturnType();
    }

    public function inNamespace(): bool
    {
        return false;
    }

    public function invoke(?object $object = null, mixed ...$args): mixed
    {
        $this->loadNative();

        return parent::invoke($object, ...$args);
    }

    public function invokeArgs(?object $object = null, array $args = []): mixed
    {
        $this->loadNative();

        return parent::invokeArgs($object, $args);
    }

    public function isAbstract(): bool
    {
        return ($this->metadata->modifiers & self::IS_ABSTRACT) !== 0;
    }

    public function isClosure(): bool
    {
        return false;
    }

    public function isConstructor(): bool
    {
        return $this->metadata->name === '__construct';
    }

    public function isDeprecated(): bool
    {
        return $this->metadata->deprecated;
    }

    public function isDestructor(): bool
    {
        return $this->metadata->name === '__destruct';
    }

    public function isFinal(Origin $origin = Origin::Resolved): bool
    {
        return match ($origin) {
            Origin::PhpDoc => $this->metadata->finalPhpDoc,
            Origin::Native => $this->metadata->finalNative(),
            Origin::Resolved => $this->metadata->finalPhpDoc || $this->metadata->finalNative(),
        };
    }

    public function isGenerator(): bool
    {
        return $this->metadata->generator;
    }

    public function isInternal(): bool
    {
        return $this->metadata->internal;
    }

    public function isPrivate(): bool
    {
        return ($this->metadata->modifiers & self::IS_PRIVATE) !== 0;
    }

    public function isProtected(): bool
    {
        return ($this->metadata->modifiers & self::IS_PROTECTED) !== 0;
    }

    public function isPublic(): bool
    {
        return ($this->metadata->modifiers & self::IS_PUBLIC) !== 0;
    }

    public function isStatic(): bool
    {
        return ($this->metadata->modifiers & self::IS_STATIC) !== 0;
    }

    public function isUserDefined(): bool
    {
        return !$this->isInternal();
    }

    public function isVariadic(): bool
    {
        $lastParameterKey = array_key_last($this->metadata->parameters);

        return $lastParameterKey !== null && $this->metadata->parameters[$lastParameterKey]->variadic;
    }

    public function returnsReference(): bool
    {
        return $this->metadata->returnsReference;
    }

    public function setAccessible(bool $accessible): void {}

    private function loadNative(): void
    {
        if (!$this->nativeLoaded) {
            parent::__construct($this->currentClass, $this->metadata->name);
            $this->nativeLoaded = true;
        }
    }
}
