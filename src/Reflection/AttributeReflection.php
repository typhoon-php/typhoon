<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\ClassConstId;
use Typhoon\DeclarationId\MethodId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\DeclarationId\ParameterId;
use Typhoon\DeclarationId\PropertyId;
use Typhoon\Reflection\Internal\ConstantExpression\Expression;
use Typhoon\Reflection\Internal\Data\Data;
use Typhoon\Reflection\Internal\NativeAdapter\AttributeAdapter;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;

/**
 * @api
 */
final class AttributeReflection
{
    /**
     * This internal property is public for testing purposes.
     * It will likely be available as part of the API in the near future.
     *
     * @internal
     * @psalm-internal Typhoon
     */
    public readonly TypedMap $data;

    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     * @param non-negative-int $index
     */
    public function __construct(
        private readonly NamedFunctionId|AnonymousFunctionId|ParameterId|NamedClassId|AnonymousClassId|ClassConstId|MethodId|PropertyId $targetId,
        private readonly int $index,
        TypedMap $data,
        private readonly TyphoonReflector $reflector,
    ) {
        $this->data = $data;
    }

    /**
     * @return non-negative-int
     */
    public function index(): int
    {
        return $this->index;
    }

    /**
     * Attribute's class.
     *
     * @return non-empty-string Not class-string, because the class might not exist. Same is true for {@see \ReflectionAttribute::getName()}.
     */
    public function className(): string
    {
        return $this->data[Data::AttributeClassName];
    }

    /**
     * Attribute's class reflection.
     *
     * @return ClassReflection<object, NamedClassId<class-string>>
     */
    public function class(): ClassReflection
    {
        /** @var ClassReflection<object, NamedClassId<class-string>> */
        return $this->reflector->reflectClass($this->className());
    }

    public function targetId(): NamedFunctionId|AnonymousFunctionId|ParameterId|NamedClassId|AnonymousClassId|ClassConstId|MethodId|PropertyId
    {
        return $this->targetId;
    }

    public function target(): FunctionReflection|ClassReflection|ClassConstReflection|PropertyReflection|MethodReflection|ParameterReflection|AliasReflection|TemplateReflection
    {
        return $this->reflector->reflect($this->targetId);
    }

    /**
     * @return ?positive-int
     */
    public function startLine(): ?int
    {
        return $this->data[Data::StartLine];
    }

    /**
     * @return ?positive-int
     */
    public function endLine(): ?int
    {
        return $this->data[Data::EndLine];
    }

    public function isRepeated(): bool
    {
        return $this->data[Data::AttributeRepeated];
    }

    public function arguments(): array
    {
        return array_map(
            fn(Expression $expression): mixed => $expression->evaluate($this->reflector),
            $this->data[Data::ArgumentExpressions],
        );
    }

    public function newInstance(): object
    {
        /** @psalm-suppress InvalidStringClass */
        return new ($this->className())(...$this->arguments());
    }

    private ?AttributeAdapter $native = null;

    public function native(): \ReflectionAttribute
    {
        return $this->native ??= new AttributeAdapter($this);
    }
}
