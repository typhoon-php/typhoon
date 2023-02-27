<?php

declare(strict_types=1);

namespace PHP\ExtendedTypeSystem\TypeStringifier;

use PHP\ExtendedTypeSystem\Type\ArrayKeyT;
use PHP\ExtendedTypeSystem\Type\ArrayShapeItem;
use PHP\ExtendedTypeSystem\Type\ArrayShapeT;
use PHP\ExtendedTypeSystem\Type\ArrayT;
use PHP\ExtendedTypeSystem\Type\AtClass;
use PHP\ExtendedTypeSystem\Type\AtFunction;
use PHP\ExtendedTypeSystem\Type\AtMethod;
use PHP\ExtendedTypeSystem\Type\BoolT;
use PHP\ExtendedTypeSystem\Type\CallableArrayT;
use PHP\ExtendedTypeSystem\Type\CallableParameter;
use PHP\ExtendedTypeSystem\Type\CallableStringT;
use PHP\ExtendedTypeSystem\Type\CallableT;
use PHP\ExtendedTypeSystem\Type\ClassConstantT;
use PHP\ExtendedTypeSystem\Type\ClassStringT;
use PHP\ExtendedTypeSystem\Type\ClosedResourceT;
use PHP\ExtendedTypeSystem\Type\ClosureT;
use PHP\ExtendedTypeSystem\Type\ConstantT;
use PHP\ExtendedTypeSystem\Type\EnumStringT;
use PHP\ExtendedTypeSystem\Type\FalseT;
use PHP\ExtendedTypeSystem\Type\FloatLiteralT;
use PHP\ExtendedTypeSystem\Type\FloatT;
use PHP\ExtendedTypeSystem\Type\InterfaceStringT;
use PHP\ExtendedTypeSystem\Type\IntersectionT;
use PHP\ExtendedTypeSystem\Type\IntLiteralT;
use PHP\ExtendedTypeSystem\Type\IntRangeT;
use PHP\ExtendedTypeSystem\Type\IntT;
use PHP\ExtendedTypeSystem\Type\IterableT;
use PHP\ExtendedTypeSystem\Type\KeyOfT;
use PHP\ExtendedTypeSystem\Type\ListT;
use PHP\ExtendedTypeSystem\Type\LiteralIntT;
use PHP\ExtendedTypeSystem\Type\LiteralStringT;
use PHP\ExtendedTypeSystem\Type\MixedT;
use PHP\ExtendedTypeSystem\Type\NamedClassStringT;
use PHP\ExtendedTypeSystem\Type\NamedObjectT;
use PHP\ExtendedTypeSystem\Type\NeverT;
use PHP\ExtendedTypeSystem\Type\NonEmptyArrayT;
use PHP\ExtendedTypeSystem\Type\NonEmptyListT;
use PHP\ExtendedTypeSystem\Type\NonEmptyStringT;
use PHP\ExtendedTypeSystem\Type\NullableT;
use PHP\ExtendedTypeSystem\Type\NullT;
use PHP\ExtendedTypeSystem\Type\NumericStringT;
use PHP\ExtendedTypeSystem\Type\NumericT;
use PHP\ExtendedTypeSystem\Type\ObjectT;
use PHP\ExtendedTypeSystem\Type\PositiveIntT;
use PHP\ExtendedTypeSystem\Type\ResourceT;
use PHP\ExtendedTypeSystem\Type\ScalarT;
use PHP\ExtendedTypeSystem\Type\StaticT;
use PHP\ExtendedTypeSystem\Type\StringLiteralT;
use PHP\ExtendedTypeSystem\Type\StringT;
use PHP\ExtendedTypeSystem\Type\TemplateT;
use PHP\ExtendedTypeSystem\Type\TraitStringT;
use PHP\ExtendedTypeSystem\Type\TrueT;
use PHP\ExtendedTypeSystem\Type\Type;
use PHP\ExtendedTypeSystem\Type\TypeAlias;
use PHP\ExtendedTypeSystem\Type\TypeVisitor;
use PHP\ExtendedTypeSystem\Type\UnionT;
use PHP\ExtendedTypeSystem\Type\ValueOfT;
use PHP\ExtendedTypeSystem\Type\VoidT;

/**
 * @psalm-api
 * @psalm-immutable
 * @implements TypeVisitor<string>
 * @psalm-suppress ImpureFunctionCall
 */
final class TypeStringifier implements TypeVisitor
{
    private function __construct()
    {
    }

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
