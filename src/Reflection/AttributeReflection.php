<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\ClassConstantId;
use Typhoon\DeclarationId\MethodId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\DeclarationId\ParameterId;
use Typhoon\DeclarationId\PropertyId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Misc\NonSerializable;
use Typhoon\Reflection\Internal\NativeAdapter\AttributeAdapter;
use Typhoon\TypedMap\TypedMap;

/**
 * @api
 */
final class AttributeReflection
{
    use NonSerializable;

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
        private readonly NamedFunctionId|AnonymousFunctionId|ParameterId|NamedClassId|AnonymousClassId|ClassConstantId|MethodId|PropertyId $targetId,
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

    public function targetId(): NamedFunctionId|AnonymousFunctionId|ParameterId|NamedClassId|AnonymousClassId|ClassConstantId|MethodId|PropertyId
    {
        return $this->targetId;
    }

    public function target(): FunctionReflection|ClassReflection|ClassConstantReflection|PropertyReflection|MethodReflection|ParameterReflection|AliasReflection|TemplateReflection
    {
        return $this->reflector->reflect($this->targetId);
    }

    public function location(): ?Location
    {
        return $this->data[Data::Location];
    }

    public function isRepeated(): bool
    {
        return $this->data[Data::AttributeRepeated];
    }

    /**
     * This method returns the actual attribute's constructor arguments and thus might trigger autoloading or throw errors.
     */
    public function evaluateArguments(): array
    {
        return $this->data[Data::ArgumentsExpression]->evaluate($this->reflector);
    }

    /**
     * @deprecated since 0.4.2 in favor of evaluateArguments()
     */
    public function arguments(): array
    {
        trigger_deprecation('typhoon/reflection', '0.4.2', 'Calling %s is deprecated in favor of %s::evaluateArguments()', __METHOD__, self::class);

        return $this->evaluateArguments();
    }

    /**
     * This method returns the actual attribute object and thus might trigger autoloading or throw errors.
     */
    public function evaluate(): object
    {
        /** @psalm-suppress InvalidStringClass */
        return new ($this->className())(...$this->evaluateArguments());
    }

    /**
     * @deprecated since 0.4.2 in favor of evaluate()
     */
    public function newInstance(): object
    {
        trigger_deprecation('typhoon/reflection', '0.4.2', 'Calling %s is deprecated in favor of %s::evaluate()', __METHOD__, self::class);

        return $this->evaluate();
    }

    private ?AttributeAdapter $native = null;

    public function toNativeReflection(): \ReflectionAttribute
    {
        return $this->native ??= new AttributeAdapter($this);
    }
}
