<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

use ExtendedTypeSystem\Type\ArrayKeyT;
use ExtendedTypeSystem\Type\ArrayShapeItem;
use ExtendedTypeSystem\Type\ArrayShapeT;
use ExtendedTypeSystem\Type\ArrayT;
use ExtendedTypeSystem\Type\AtClass;
use ExtendedTypeSystem\Type\AtFunction;
use ExtendedTypeSystem\Type\AtMethod;
use ExtendedTypeSystem\Type\BoolT;
use ExtendedTypeSystem\Type\CallableArrayT;
use ExtendedTypeSystem\Type\CallableParameter;
use ExtendedTypeSystem\Type\CallableStringT;
use ExtendedTypeSystem\Type\CallableT;
use ExtendedTypeSystem\Type\ClassConstantT;
use ExtendedTypeSystem\Type\ClassStringT;
use ExtendedTypeSystem\Type\ClosedResourceT;
use ExtendedTypeSystem\Type\ClosureT;
use ExtendedTypeSystem\Type\ConstantT;
use ExtendedTypeSystem\Type\EnumStringT;
use ExtendedTypeSystem\Type\FalseT;
use ExtendedTypeSystem\Type\FloatLiteralT;
use ExtendedTypeSystem\Type\FloatT;
use ExtendedTypeSystem\Type\InterfaceStringT;
use ExtendedTypeSystem\Type\IntersectionT;
use ExtendedTypeSystem\Type\IntLiteralT;
use ExtendedTypeSystem\Type\IntRangeT;
use ExtendedTypeSystem\Type\IntT;
use ExtendedTypeSystem\Type\IterableT;
use ExtendedTypeSystem\Type\KeyOfT;
use ExtendedTypeSystem\Type\ListT;
use ExtendedTypeSystem\Type\LiteralIntT;
use ExtendedTypeSystem\Type\LiteralStringT;
use ExtendedTypeSystem\Type\MixedT;
use ExtendedTypeSystem\Type\NamedClassStringT;
use ExtendedTypeSystem\Type\NamedObjectT;
use ExtendedTypeSystem\Type\NeverT;
use ExtendedTypeSystem\Type\NonEmptyArrayT;
use ExtendedTypeSystem\Type\NonEmptyListT;
use ExtendedTypeSystem\Type\NonEmptyStringT;
use ExtendedTypeSystem\Type\NullableT;
use ExtendedTypeSystem\Type\NullT;
use ExtendedTypeSystem\Type\NumericStringT;
use ExtendedTypeSystem\Type\NumericT;
use ExtendedTypeSystem\Type\ObjectT;
use ExtendedTypeSystem\Type\PositiveIntT;
use ExtendedTypeSystem\Type\ResourceT;
use ExtendedTypeSystem\Type\ScalarT;
use ExtendedTypeSystem\Type\StaticT;
use ExtendedTypeSystem\Type\StringLiteralT;
use ExtendedTypeSystem\Type\StringT;
use ExtendedTypeSystem\Type\TemplateT;
use ExtendedTypeSystem\Type\TraitStringT;
use ExtendedTypeSystem\Type\TrueT;
use ExtendedTypeSystem\Type\UnionT;
use ExtendedTypeSystem\Type\ValueOfT;
use ExtendedTypeSystem\Type\VoidT;

/**
 * @psalm-api
 * @psalm-immutable
 * @implements TypeVisitor<non-empty-string>
 * @psalm-suppress ImpureFunctionCall
 */
final class TypeStringifier implements TypeVisitor
{
    private function __construct()
    {
    }

    /**
     * @return non-empty-string
     */
    public static function stringify(Type $type): string
    {
        return $type->accept(new self());
    }

    public function visitNever(NeverT $type): mixed
    {
        return 'never';
    }

    public function visitVoid(VoidT $type): mixed
    {
        return 'void';
    }

    public function visitNull(NullT $type): mixed
    {
        return 'null';
    }

    public function visitFalse(FalseT $type): mixed
    {
        return 'false';
    }

    public function visitTrue(TrueT $type): mixed
    {
        return 'true';
    }

    public function visitBool(BoolT $type): mixed
    {
        return 'bool';
    }

    public function visitIntLiteral(IntLiteralT $type): mixed
    {
        return (string) $type->value;
    }

    public function visitLiteralInt(LiteralIntT $type): mixed
    {
        return 'literal-int';
    }

    public function visitIntRange(IntRangeT $type): mixed
    {
        if ($type->min === null && $type->max === null) {
            return 'int';
        }

        return sprintf('int<%s, %s>', $type->min ?? 'min', $type->max ?? 'max');
    }

    public function visitInt(IntT $type): mixed
    {
        return 'int';
    }

    public function visitFloatLiteral(FloatLiteralT $type): mixed
    {
        return (string) $type->value;
    }

    public function visitFloat(FloatT $type): mixed
    {
        return 'float';
    }

    public function visitStringLiteral(StringLiteralT $type): mixed
    {
        return var_export($type->value, return: true);
    }

    public function visitLiteralString(LiteralStringT $type): mixed
    {
        return 'literal-string';
    }

    public function visitNonEmptyString(NonEmptyStringT $type): mixed
    {
        return 'non-empty-string';
    }

    public function visitNamedClassString(NamedClassStringT $type): mixed
    {
        return sprintf('class-string<%s>', $type->type->accept($this));
    }

    public function visitClassString(ClassStringT $type): mixed
    {
        return 'class-string';
    }

    public function visitCallableString(CallableStringT $type): mixed
    {
        return 'callable-string';
    }

    public function visitInterfaceString(InterfaceStringT $type): mixed
    {
        return 'interface-string';
    }

    public function visitEnumString(EnumStringT $type): mixed
    {
        return 'enum-string';
    }

    public function visitTraitString(TraitStringT $type): mixed
    {
        return 'trait-string';
    }

    public function visitString(StringT $type): mixed
    {
        return 'string';
    }

    public function visitNumeric(NumericT $type): mixed
    {
        return 'numeric';
    }

    public function visitNonEmptyList(NonEmptyListT $type): mixed
    {
        if ($type->valueType instanceof MixedT) {
            return 'non-empty-list';
        }

        return $this->stringifyGenericType('non-empty-list', [$type->valueType]);
    }

    public function visitList(ListT $type): mixed
    {
        if ($type->valueType instanceof MixedT) {
            return 'list';
        }

        return $this->stringifyGenericType('list', [$type->valueType]);
    }

    public function visitArrayShape(ArrayShapeT $type): mixed
    {
        if (!$type->sealed && $type->items === []) {
            return 'array';
        }

        $list = array_is_list($type->items);

        return sprintf(
            '%s{%s%s}',
            $type->sealed && $list ? 'list' : 'array',
            implode(', ', array_map(
                fn (int|string $key, ArrayShapeItem $item): string => $this->stringifyArrayShapeItem($list, $key, $item),
                array_keys($type->items),
                $type->items,
            )),
            $type->sealed ? '' : ', ...',
        );
    }

    public function visitNonEmptyArray(NonEmptyArrayT $type): mixed
    {
        if ($type->keyType instanceof ArrayKeyT) {
            if ($type->valueType instanceof MixedT) {
                return 'non-empty-array';
            }

            return $this->stringifyGenericType('non-empty-array', [$type->valueType]);
        }

        return $this->stringifyGenericType('non-empty-array', [$type->keyType, $type->valueType]);
    }

    public function visitCallableArray(CallableArrayT $type): mixed
    {
        return 'callable-array';
    }

    public function visitArray(ArrayT $type): mixed
    {
        if ($type->keyType instanceof ArrayKeyT) {
            if ($type->valueType instanceof MixedT) {
                return 'array';
            }

            return $this->stringifyGenericType('array', [$type->valueType]);
        }

        return $this->stringifyGenericType('array', [$type->keyType, $type->valueType]);
    }

    public function visitIterable(IterableT $type): mixed
    {
        if ($type->keyType instanceof MixedT) {
            if ($type->valueType instanceof MixedT) {
                return 'iterable';
            }

            return $this->stringifyGenericType('iterable', [$type->valueType]);
        }

        return $this->stringifyGenericType('iterable', [$type->keyType, $type->valueType]);
    }

    public function visitNamedObject(NamedObjectT $type): mixed
    {
        if ($type->templateArguments === []) {
            return $type->class;
        }

        return $this->stringifyGenericType($type->class, $type->templateArguments);
    }

    public function visitStatic(StaticT $type): mixed
    {
        if ($type->templateArguments === []) {
            return 'static';
        }

        return $this->stringifyGenericType('static', $type->templateArguments);
    }

    public function visitObject(ObjectT $type): mixed
    {
        return 'object';
    }

    public function visitResource(ResourceT $type): mixed
    {
        return 'resource';
    }

    public function visitClosedResource(ClosedResourceT $type): mixed
    {
        return 'closed-resource';
    }

    public function visitClosure(ClosureT $type): mixed
    {
        return $this->stringifyCallable('Closure', $type->parameters, $type->returnType);
    }

    public function visitCallable(CallableT $type): mixed
    {
        return $this->stringifyCallable('callable', $type->parameters, $type->returnType);
    }

    public function visitConstant(ConstantT $type): mixed
    {
        return $type->constant;
    }

    public function visitClassConstant(ClassConstantT $type): mixed
    {
        return sprintf('%s::%s', $type->class, $type->constant);
    }

    public function visitKeyOf(KeyOfT $type): mixed
    {
        return $this->stringifyGenericType('key-of', [$type->type]);
    }

    public function visitValueOf(ValueOfT $type): mixed
    {
        return $this->stringifyGenericType('value-of', [$type->type]);
    }

    public function visitTemplate(TemplateT $type): mixed
    {
        return sprintf('%s:%s', $type->name, $this->stringifyAt($type->declaredAt));
    }

    public function visitIntersection(IntersectionT $type): mixed
    {
        /** @psalm-suppress MixedArgument */
        return implode('&', array_map(
            fn (Type $inner): string => $inner instanceof UnionT ? sprintf('(%s)', $inner->accept($this)) : $inner->accept($this),
            $type->types,
        ));
    }

    public function visitUnion(UnionT $type): mixed
    {
        /** @psalm-suppress MixedArgument */
        return implode('|', array_map(
            fn (Type $inner): string => $inner instanceof IntersectionT ? sprintf('(%s)', $inner->accept($this)) : $inner->accept($this),
            $type->types,
        ));
    }

    public function visitMixed(MixedT $type): mixed
    {
        return 'mixed';
    }

    public function visitAlias(TypeAlias $type): mixed
    {
        return match ($type::class) {
            NullableT::class => '?'.$type->type->accept($this),
            PositiveIntT::class => 'positive-int',
            NumericStringT::class => 'numeric-string',
            ArrayKeyT::class => 'array-key',
            ScalarT::class => 'scalar',
            default => $type->type()->accept($this),
        };
    }

    private function stringifyArrayShapeItem(bool $list, int|string $key, ArrayShapeItem $item): string
    {
        if ($list && !$item->optional) {
            return $item->type->accept($this);
        }

        return sprintf('%s%s: %s', $key, $item->optional ? '?' : '', $item->type->accept($this));
    }

    /**
     * @param non-empty-list<Type> $templateArguments
     */
    private function stringifyGenericType(string $name, array $templateArguments): string
    {
        return sprintf('%s<%s>', $name, implode(', ', array_map(
            fn (Type $type): string => $type->accept($this),
            $templateArguments,
        )));
    }

    private function stringifyAt(AtFunction|AtClass|AtMethod $at): string
    {
        return match ($at::class) {
            AtFunction::class => sprintf('%s()', $at->function),
            AtClass::class => $at->class,
            AtMethod::class => sprintf('%s::%s()', $at->class, $at->method),
        };
    }

    /**
     * @param list<CallableParameter> $parameters
     */
    private function stringifyCallable(string $name, array $parameters, ?Type $returnType): string
    {
        if ($parameters === [] && $returnType === null) {
            return $name;
        }

        return sprintf(
            '%s(%s)%s',
            $name,
            implode(', ', array_map(
                fn (CallableParameter $parameter): string => $parameter->type->accept($this).match (true) {
                    $parameter->variadic => '...',
                    $parameter->hasDefault => '=',
                    default => '',
                },
                $parameters,
            )),
            $returnType === null ? '' : ': '.$returnType->accept($this),
        );
    }
}
