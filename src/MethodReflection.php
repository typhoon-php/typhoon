<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Reflector\FriendlyReflection;
use Typhoon\Type\TypeVisitor;

/**
 * @api
 */
final class MethodReflection extends FriendlyReflection
{
    public const IS_FINAL = \ReflectionMethod::IS_FINAL;
    public const IS_ABSTRACT = \ReflectionMethod::IS_ABSTRACT;
    public const IS_PUBLIC = \ReflectionMethod::IS_PUBLIC;
    public const IS_PROTECTED = \ReflectionMethod::IS_PROTECTED;
    public const IS_PRIVATE = \ReflectionMethod::IS_PRIVATE;
    public const IS_STATIC = \ReflectionMethod::IS_STATIC;

    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     * @param class-string $class
     * @param non-empty-string $name
     * @param list<TemplateReflection> $templates
     * @param list<ParameterReflection> $parameters
     * @param ?non-empty-string $docComment
     * @param ?non-empty-string $extensionName
     * @param ?non-empty-string $file
     * @param ?positive-int $startLine
     * @param ?positive-int $endLine
     * @param int-mask-of<self::IS_*> $modifiers
     */
    public function __construct(
        public readonly string $class,
        public readonly string $name,
        private readonly array $templates,
        private readonly int $modifiers,
        private readonly ?string $docComment,
        private readonly bool $internal,
        private readonly ?string $extensionName,
        private readonly ?string $file,
        private readonly ?int $startLine,
        private readonly ?int $endLine,
        private readonly bool $returnsReference,
        private readonly bool $generator,
        /** @readonly */
        private array $parameters,
        /** @readonly */
        private TypeReflection $returnType,
        private ?\ReflectionMethod $reflectionMethod = null,
    ) {}

    /**
     * @return non-empty-string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return non-empty-string
     */
    public function getShortName(): string
    {
        return $this->name;
    }

    public function inNamespace(): bool
    {
        return false;
    }

    public function getNamespaceName(): string
    {
        return '';
    }

    /**
     * @return ?non-empty-string
     */
    public function getExtensionName(): ?string
    {
        return $this->extensionName;
    }

    public function isInternal(): bool
    {
        return $this->internal;
    }

    public function isUserDefined(): bool
    {
        return !$this->internal;
    }

    /**
     * @return ?non-empty-string
     */
    public function getFileName(): ?string
    {
        return $this->file;
    }

    /**
     * @return ?positive-int
     */
    public function getStartLine(): ?int
    {
        return $this->startLine;
    }

    /**
     * @return ?positive-int
     */
    public function getEndLine(): ?int
    {
        return $this->endLine;
    }

    /**
     * @return ?non-empty-string
     */
    public function getDocComment(): ?string
    {
        return $this->docComment;
    }

    /**
     * @return list<TemplateReflection>
     */
    public function getTemplates(): array
    {
        return $this->templates;
    }

    /**
     * @psalm-assert-if-true non-empty-string $name
     */
    public function hasTemplateWithName(string $name): bool
    {
        foreach ($this->templates as $template) {
            if ($template->name === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @psalm-assert-if-true int<0, max> $position
     */
    public function hasTemplateWithPosition(int $position): bool
    {
        return isset($this->templates[$position]);
    }

    /**
     * @psalm-assert int<0, max> $position
     */
    public function getTemplateByPosition(int $position): TemplateReflection
    {
        return $this->templates[$position] ?? throw new ReflectionException();
    }

    /**
     * @psalm-assert non-empty-string $name
     */
    public function getTemplateByName(string $name): TemplateReflection
    {
        foreach ($this->templates as $template) {
            if ($template->name === $name) {
                return $template;
            }
        }

        throw new ReflectionException();
    }

    /**
     * @return int-mask-of<self::IS_*>
     */
    public function getModifiers(): int
    {
        return $this->modifiers;
    }

    public function isFinal(): bool
    {
        return ($this->modifiers & self::IS_FINAL) !== 0;
    }

    public function isAbstract(): bool
    {
        return ($this->modifiers & self::IS_ABSTRACT) !== 0;
    }

    public function isStatic(): bool
    {
        return ($this->modifiers & self::IS_STATIC) !== 0;
    }

    public function isPublic(): bool
    {
        return ($this->modifiers & self::IS_PUBLIC) !== 0;
    }

    public function isProtected(): bool
    {
        return ($this->modifiers & self::IS_PROTECTED) !== 0;
    }

    public function isPrivate(): bool
    {
        return ($this->modifiers & self::IS_PRIVATE) !== 0;
    }

    public function isVariadic(): bool
    {
        $lastParameterKey = array_key_last($this->parameters);

        return $lastParameterKey !== null && $this->parameters[$lastParameterKey]->isVariadic();
    }

    public function isConstructor(): bool
    {
        return $this->name === '__construct';
    }

    public function isDestructor(): bool
    {
        return $this->name === '__destruct';
    }

    /**
     * @return false
     */
    public function isClosure(): bool
    {
        return false;
    }

    public function isGenerator(): bool
    {
        return $this->generator;
    }

    public function returnsReference(): bool
    {
        return $this->returnsReference;
    }

    /**
     * @return int<0, max>
     */
    public function getNumberOfParameters(): int
    {
        return \count($this->parameters);
    }

    /**
     * @return int<0, max>
     */
    public function getNumberOfRequiredParameters(): int
    {
        foreach ($this->parameters as $parameter) {
            if ($parameter->isOptional()) {
                return $parameter->getPosition();
            }
        }

        return $this->getNumberOfParameters();
    }

    /**
     * @return list<ParameterReflection>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @psalm-assert-if-true non-empty-string $name
     */
    public function hasParameterWithName(string $name): bool
    {
        foreach ($this->parameters as $parameter) {
            if ($parameter->name === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @psalm-assert-if-true int<0, max> $position
     */
    public function hasParameterWithPosition(int $position): bool
    {
        return isset($this->parameters[$position]);
    }

    /**
     * @psalm-assert int<0, max> $position
     */
    public function getParameterByPosition(int $position): ParameterReflection
    {
        return $this->parameters[$position] ?? throw new ReflectionException();
    }

    /**
     * @psalm-assert non-empty-string $name
     */
    public function getParameterByName(string $name): ParameterReflection
    {
        foreach ($this->parameters as $parameter) {
            if ($parameter->name === $name) {
                return $parameter;
            }
        }

        throw new ReflectionException();
    }

    public function getReturnType(): TypeReflection
    {
        return $this->returnType;
    }

    public function invoke(?object $object = null, mixed ...$args): mixed
    {
        return $this->reflectionMethod()->invoke($object, ...$args);
    }

    public function invokeArgs(?object $object = null, array $args = []): mixed
    {
        return $this->reflectionMethod()->invokeArgs($object, $args);
    }

    public function getClosure(?object $object = null): \Closure
    {
        return $this->reflectionMethod()->getClosure($object);
    }

    public function __serialize(): array
    {
        $data = get_object_vars($this);
        unset($data['reflectionMethod']);

        return $data;
    }

    public function __clone()
    {
        if ((debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['class'] ?? null) !== self::class) {
            throw new ReflectionException();
        }
    }

    protected function withResolvedTypes(TypeVisitor $typeResolver): static
    {
        $method = clone $this;
        $method->parameters = array_map(
            static fn (ParameterReflection $parameter): ParameterReflection => $parameter->withResolvedTypes($typeResolver),
            $this->parameters,
        );
        $method->returnType->withResolvedTypes($typeResolver);

        return $method;
    }

    protected function toChildOf(FriendlyReflection $parent): static
    {
        $method = clone $this;
        $parentParametersByPosition = $parent->parameters;
        $method->parameters = array_map(
            static fn (ParameterReflection $parameter): ParameterReflection => isset($parentParametersByPosition[$parameter->getPosition()])
                ? $parameter->toChildOf($parentParametersByPosition[$parameter->getPosition()])
                : $parameter,
            $this->parameters,
        );
        $method->returnType->toChildOf($parent->returnType);

        return $method;
    }

    private function reflectionMethod(): \ReflectionMethod
    {
        return $this->reflectionMethod ??= new \ReflectionMethod($this->class, $this->name);
    }
}
