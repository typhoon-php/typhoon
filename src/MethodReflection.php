<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Reflector\FriendlyReflection;
use Typhoon\TypeVisitor;

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
     * @param class-string $class
     * @param non-empty-string $name
     * @param list<TemplateReflection> $templates
     * @param list<ParameterReflection> $parameters
     * @param ?non-empty-string $docComment
     * @param ?non-empty-string $extensionName
     * @param ?non-empty-string $fileName
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
        private readonly ?string $fileName,
        private readonly ?int $startLine,
        private readonly ?int $endLine,
        private readonly bool $returnsReference,
        private readonly bool $generator,
        private readonly array $parameters,
        private readonly TypeReflection $returnType,
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
        return $this->fileName;
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

    public function hasParameter(int|string $positionOrName): bool
    {
        if (\is_int($positionOrName)) {
            return isset($this->parameters[$positionOrName]);
        }

        foreach ($this->parameters as $parameter) {
            if ($parameter->name === $positionOrName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<ParameterReflection>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getParameter(int|string $positionOrName): ParameterReflection
    {
        if (\is_int($positionOrName)) {
            return $this->parameters[$positionOrName] ?? throw new ReflectionException();
        }

        foreach ($this->parameters as $parameter) {
            if ($parameter->name === $positionOrName) {
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

    protected function withResolvedTypes(TypeVisitor $typeResolver): static
    {
        $data = get_object_vars($this);
        $data['parameters'] = array_map(
            static fn (ParameterReflection $parameter): ParameterReflection => $parameter->withResolvedTypes($typeResolver),
            $this->parameters,
        );
        $data['returnType'] = $this->returnType->withResolvedTypes($typeResolver);
        $data['modifiers'] = $this->modifiers;

        return new self(...$data);
    }

    protected function toChildOf(FriendlyReflection $parent): static
    {
        $data = get_object_vars($this);

        $parentParametersByPosition = $parent->parameters;
        $data['parameters'] = array_map(
            static fn (ParameterReflection $parameter): ParameterReflection => isset($parentParametersByPosition[$parameter->getPosition()])
                ? $parameter->toChildOf($parentParametersByPosition[$parameter->getPosition()])
                : $parameter,
            $this->parameters,
        );
        $data['returnType'] = $this->returnType->toChildOf($parent->returnType);
        $data['modifiers'] = $this->modifiers;

        return new self(...$data);
    }

    private function reflectionMethod(): \ReflectionMethod
    {
        return $this->reflectionMethod ??= new \ReflectionMethod($this->class, $this->name);
    }

    private function __clone() {}
}
