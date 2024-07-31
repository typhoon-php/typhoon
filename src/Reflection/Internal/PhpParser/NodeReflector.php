<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpParser;

use PhpParser\Node;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\EnumCase;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\Stmt\TraitUseAdaptation\Alias;
use PhpParser\Node\Stmt\TraitUseAdaptation\Precedence;
use PhpParser\Node\UnionType;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\ConstantExpression\ArrayElement;
use Typhoon\Reflection\Internal\ConstantExpression\ArrayExpression;
use Typhoon\Reflection\Internal\ConstantExpression\Expression;
use Typhoon\Reflection\Internal\ConstantExpression\Value;
use Typhoon\Reflection\Internal\ConstantExpression\Values;
use Typhoon\Reflection\Internal\Context\Context;
use Typhoon\Reflection\Internal\Context\ContextVisitor;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Data\ClassKind;
use Typhoon\Reflection\Internal\Data\PassedBy;
use Typhoon\Reflection\Internal\Data\TraitMethodAlias;
use Typhoon\Reflection\Internal\Data\TypeData;
use Typhoon\Reflection\Internal\Data\Visibility;
use Typhoon\Reflection\Internal\NativeAdapter\NativeTraitInfo;
use Typhoon\Reflection\Internal\NativeAdapter\NativeTraitInfoKey;
use Typhoon\Reflection\Internal\Type\IsNativeTypeNullable;
use Typhoon\Reflection\Location;
use Typhoon\Type\Type;
use Typhoon\Type\types;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class NodeReflector
{
    public function reflectClassLike(ClassLike $node): TypedMap
    {
        $context = ContextVisitor::fromNode($node);

        $data = (new TypedMap())
            ->with(Data::PhpDoc, $node->getDocComment())
            ->with(Data::Location, $this->reflectLocation($context, $node))
            ->with(Data::Context, $context)
            ->with(Data::Namespace, $context->namespace())
            ->with(Data::Attributes, $this->reflectAttributes($context, $node->attrGroups))
            ->with(Data::Constants, $this->reflectConstants($context, $node->stmts));

        if ($node instanceof Class_) {
            return $data
                ->with(Data::ClassKind, ClassKind::Class_)
                ->with(Data::UnresolvedParent, $node->extends === null ? null : [$node->extends->toString(), []])
                ->with(Data::UnresolvedInterfaces, $this->reflectInterfaces($node->implements))
                ->withMap($this->reflectTraitUses($node->getTraitUses()))
                ->with(Data::Abstract, $node->isAbstract())
                ->with(Data::NativeReadonly, $node->isReadonly())
                ->with(Data::NativeFinal, $node->isFinal())
                ->with(Data::Properties, $this->reflectProperties($context, $node->getProperties()))
                ->with(Data::Methods, $this->reflectMethods($node->getMethods()));
        }

        if ($node instanceof Interface_) {
            return $data
                ->with(Data::ClassKind, ClassKind::Interface)
                ->with(Data::UnresolvedInterfaces, $this->reflectInterfaces($node->extends))
                ->with(Data::Methods, $this->reflectMethods($node->getMethods()));
        }

        if ($node instanceof Enum_) {
            /** @var ?Type<int|string> */
            $backingType = $this->reflectType($context, $node->scalarType);

            return $data
                ->with(Data::ClassKind, ClassKind::Enum)
                ->with(Data::UnresolvedInterfaces, $this->reflectInterfaces($node->implements))
                ->withMap($this->reflectTraitUses($node->getTraitUses()))
                ->with(Data::NativeFinal, true)
                ->with(Data::BackingType, $backingType)
                ->with(Data::Methods, $this->reflectMethods($node->getMethods()));
        }

        \assert($node instanceof Trait_, 'Unknown ClassLike node %s' . $node::class);

        return $data
            ->with(Data::ClassKind, ClassKind::Trait)
            ->withMap($this->reflectTraitUses($node->getTraitUses()))
            ->with(Data::Properties, $this->reflectProperties($context, $node->getProperties()))
            ->with(Data::Methods, $this->reflectMethods($node->getMethods()));
    }

    public function reflectFunction(Function_ $node): TypedMap
    {
        return $this
            ->reflectFunctionLike($node)
            ->with(Data::Namespace, ContextVisitor::fromNode($node)->namespace());
    }

    /**
     * @param array<Name> $names
     * @return array<non-empty-string, list<Type>>
     */
    private function reflectInterfaces(array $names): array
    {
        $interfaces = [];

        foreach ($names as $name) {
            $interfaces[$name->toString()] = [];
        }

        return $interfaces;
    }

    /**
     * @param array<TraitUse> $nodes
     */
    private function reflectTraitUses(array $nodes): TypedMap
    {
        $allNames = [];
        $traits = [];
        $precedence = [];
        $phpDocs = [];

        foreach ($nodes as $node) {
            $phpDoc = $node->getDocComment();

            if ($phpDoc !== null) {
                $phpDocs[] = $phpDoc;
            }

            foreach ($node->traits as $name) {
                $traits[$name->toString()] = [];
                $allNames[] = $name->toString();
            }

            foreach ($node->adaptations as $adaptation) {
                if ($adaptation instanceof Precedence) {
                    \assert($adaptation->trait !== null);
                    $precedence[$adaptation->method->name] = $adaptation->trait->toString();
                }
            }
        }

        $aliases = $this->reflectTraitAliases($nodes, array_keys($traits));

        return (new TypedMap())
            ->with(Data::UnresolvedTraits, $traits)
            ->with(Data::TraitMethodPrecedence, $precedence)
            ->with(Data::TraitMethodAliases, $aliases)
            ->with(Data::UsePhpDocs, $phpDocs)
            ->with(NativeTraitInfoKey::Key, new NativeTraitInfo($allNames, $aliases));
    }

    /**
     * @param array<TraitUse> $nodes
     * @param list<non-empty-string> $traits
     * @return list<TraitMethodAlias>
     */
    private function reflectTraitAliases(array $nodes, array $traits): array
    {
        $aliases = [];

        foreach ($nodes as $node) {
            foreach ($node->adaptations as $adaptation) {
                if ($adaptation instanceof Alias) {
                    if ($adaptation->trait === null) {
                        $aliasTraits = $traits;
                    } else {
                        $aliasTraits = [$adaptation->trait->toString()];
                    }

                    foreach ($aliasTraits as $aliasTrait) {
                        $aliases[] = new TraitMethodAlias(
                            trait: $aliasTrait,
                            method: $adaptation->method->name,
                            newName: $adaptation->newName?->name,
                            newVisibility: $adaptation->newModifier === null ? null : $this->reflectVisibility($adaptation->newModifier),
                        );
                    }
                }
            }
        }

        return $aliases;
    }

    /**
     * @param array<Stmt> $nodes
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectConstants(Context $context, array $nodes): array
    {
        $compiler = new ConstantExpressionCompiler($context);
        $valueTypeReflector = new ConstantExpressionTypeReflector($context);
        $constants = [];
        $enumType = null;

        foreach ($nodes as $node) {
            if ($node instanceof ClassConst) {
                $data = (new TypedMap())
                    ->with(Data::PhpDoc, $node->getDocComment())
                    ->with(Data::Location, $this->reflectLocation($context, $node))
                    ->with(Data::Attributes, $this->reflectAttributes($context, $node->attrGroups))
                    ->with(Data::NativeFinal, $node->isFinal())
                    ->with(Data::Visibility, $this->reflectVisibility($node->flags));
                $nativeType = $this->reflectType($context, $node->type);

                foreach ($node->consts as $const) {
                    $constants[$const->name->name] = $data
                        ->with(Data::ValueExpression, $compiler->compile($const->value))
                        ->with(Data::Type, new TypeData(
                            native: $nativeType,
                            value: $valueTypeReflector->reflect($const->value),
                        ));
                }

                continue;
            }

            if ($node instanceof EnumCase) {
                if ($enumType === null) {
                    \assert($context->currentId instanceof NamedClassId, 'Enum cannot be an anonymous class');
                    $enumType = types::object($context->currentId);
                }

                $constants[$node->name->name] = (new TypedMap())
                    ->with(Data::PhpDoc, $node->getDocComment())
                    ->with(Data::Location, $this->reflectLocation($context, $node))
                    ->with(Data::Attributes, $this->reflectAttributes($context, $node->attrGroups))
                    ->with(Data::EnumCase, true)
                    ->with(Data::Type, new TypeData(value: types::classConstant($enumType, $node->name->name)))
                    ->with(Data::Visibility, Visibility::Public)
                    ->with(Data::BackingValueExpression, $compiler->compile($node->expr));

                continue;
            }
        }

        return $constants;
    }

    /**
     * @param array<Property> $nodes
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectProperties(Context $context, array $nodes): array
    {
        $compiler = new ConstantExpressionCompiler($context);
        $properties = [];

        foreach ($nodes as $node) {
            $data = (new TypedMap())
                ->with(Data::PhpDoc, $node->getDocComment())
                ->with(Data::Location, $this->reflectLocation($context, $node))
                ->with(Data::Attributes, $this->reflectAttributes($context, $node->attrGroups))
                ->with(Data::Static, $node->isStatic())
                ->with(Data::NativeReadonly, $node->isReadonly())
                ->with(Data::Type, new TypeData($this->reflectType($context, $node->type)))
                ->with(Data::Visibility, $this->reflectVisibility($node->flags));

            foreach ($node->props as $prop) {
                $default = $compiler->compile($prop->default);

                if ($default === null && $node->type === null) {
                    $default = Values::Null;
                }

                $properties[$prop->name->name] = $data->with(Data::DefaultValueExpression, $default);
            }
        }

        return $properties;
    }

    private function reflectFunctionLike(FunctionLike $node): TypedMap
    {
        $context = ContextVisitor::fromNode($node);

        return (new TypedMap())
            ->with(Data::PhpDoc, $node->getDocComment())
            ->with(Data::Location, $this->reflectLocation($context, $node))
            ->with(Data::Context, $context)
            ->with(Data::Type, new TypeData($this->reflectType($context, $node->getReturnType())))
            ->with(Data::ReturnsReference, $node->returnsByRef())
            ->with(Data::Generator, GeneratorVisitor::isGenerator($node))
            ->with(Data::Attributes, $this->reflectAttributes($context, $node->getAttrGroups()))
            ->with(Data::Parameters, $this->reflectParameters($context, $node->getParams()));
    }

    /**
     * @param array<ClassMethod> $nodes
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectMethods(array $nodes): array
    {
        $methods = [];

        foreach ($nodes as $node) {
            $methods[$node->name->name] = $this->reflectFunctionLike($node)
                ->with(Data::Visibility, $this->reflectVisibility($node->flags))
                ->with(Data::Static, $node->isStatic())
                ->with(Data::NativeFinal, $node->isFinal())
                ->with(Data::Abstract, $node->isAbstract());
        }

        return $methods;
    }

    /**
     * @param array<Node\Param> $nodes
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectParameters(Context $context, array $nodes): array
    {
        $compiler = new ConstantExpressionCompiler($context);
        $parameters = [];

        foreach ($nodes as $node) {
            \assert($node->var instanceof Variable && \is_string($node->var->name));

            $default = $compiler->compile($node->default);

            $parameters[$node->var->name] = (new TypedMap())
                ->with(Data::PhpDoc, $node->getDocComment())
                ->with(Data::Location, $this->reflectLocation($context, $node))
                ->with(Data::Visibility, $this->reflectVisibility($node->flags))
                ->with(Data::Attributes, $this->reflectAttributes($context, $node->attrGroups))
                ->with(Data::Type, new TypeData($this->reflectParameterType($context, $node->type, $default)))
                ->with(Data::PassedBy, $node->byRef ? PassedBy::Reference : PassedBy::Value)
                ->with(Data::DefaultValueExpression, $default)
                ->with(Data::Promoted, $node->flags !== 0)
                ->with(Data::NativeReadonly, (bool) ($node->flags & Class_::MODIFIER_READONLY))
                ->with(Data::Variadic, $node->variadic);
        }

        return $parameters;
    }

    private function reflectParameterType(Context $context, null|Name|Identifier|ComplexType $node, ?Expression $default): ?Type
    {
        $type = $this->reflectType($context, $node);

        /**
         * Parameter of myFunction(string $param = null) has an implicitly nullable string type.
         */
        if ($default === Values::Null && $type !== null && !$type->accept(new IsNativeTypeNullable())) {
            return types::nullable($type);
        }

        return $type;
    }

    /**
     * @param array<AttributeGroup> $attributeGroups
     * @return list<TypedMap>
     */
    private function reflectAttributes(Context $context, array $attributeGroups): array
    {
        $compiler = new ConstantExpressionCompiler($context);
        $attributes = [];

        foreach ($attributeGroups as $attributeGroup) {
            foreach ($attributeGroup->attrs as $attr) {
                $attributes[] = (new TypedMap())
                    ->with(Data::Location, $this->reflectLocation($context, $attr))
                    ->with(Data::AttributeClassName, $attr->name->toString())
                    ->with(Data::ArgumentsExpression, $this->reflectArguments($compiler, $attr->args));
            }
        }

        return $attributes;
    }

    /**
     * @param array<Node\Arg> $nodes
     * @return Expression<array>
     */
    private function reflectArguments(ConstantExpressionCompiler $compiler, array $nodes): Expression
    {
        $elements = [];

        foreach ($nodes as $node) {
            $elements[] = new ArrayElement(
                key: $node->name === null ? null : Value::from($node->name->name),
                value: $compiler->compile($node->value),
            );
        }

        if ($elements === []) {
            return Value::from([]);
        }

        return new ArrayExpression($elements);
    }

    /**
     * @return ($node is null ? null : Type)
     */
    private function reflectType(Context $context, null|Name|Identifier|ComplexType $node): ?Type
    {
        if ($node === null) {
            return null;
        }

        if ($node instanceof NullableType) {
            return types::nullable($this->reflectType($context, $node->type));
        }

        if ($node instanceof UnionType) {
            return types::union(...array_map(
                fn(Node $child): Type => $this->reflectType($context, $child),
                $node->types,
            ));
        }

        if ($node instanceof IntersectionType) {
            return types::intersection(...array_map(
                fn(Node $child): Type => $this->reflectType($context, $child),
                $node->types,
            ));
        }

        if ($node instanceof Identifier) {
            return match ($node->name) {
                'never' => types::never,
                'void' => types::void,
                'null' => types::null,
                'true' => types::true,
                'false' => types::false,
                'bool' => types::bool,
                'int' => types::int,
                'float' => types::float,
                'string' => types::string,
                'array' => types::array,
                'object' => types::object,
                'callable' => types::callable,
                'iterable' => types::iterable,
                'resource' => types::resource,
                'mixed' => types::mixed,
                default => throw new \LogicException(\sprintf('Native type "%s" is not supported', $node->name)),
            };
        }

        if ($node instanceof Name) {
            return $context->resolveNameAsType($node->toCodeString());
        }

        /** @psalm-suppress MixedArgument */
        throw new \LogicException(\sprintf('Type node of class %s is not supported', $node::class));
    }

    private function reflectLocation(Context $context, Node $node): Location
    {
        $startPosition = $node->getStartFilePos();
        $endPosition = $node->getEndFilePos();

        if ($startPosition < 0 || $endPosition < 0) {
            throw new \LogicException();
        }

        $startLine = $node->getStartLine();
        $endLine = $node->getEndLine();

        if ($startLine < 1 || $endLine < 1) {
            throw new \LogicException();
        }

        ++$endPosition;

        return new Location(
            startPosition: $startPosition,
            endPosition: $endPosition,
            startLine: $startLine,
            endLine: $endLine,
            startColumn: $context->column($startPosition),
            endColumn: $context->column($endPosition),
        );
    }

    private function reflectVisibility(int $flags): ?Visibility
    {
        return match (true) {
            (bool) ($flags & Class_::MODIFIER_PUBLIC) => Visibility::Public,
            (bool) ($flags & Class_::MODIFIER_PROTECTED) => Visibility::Protected,
            (bool) ($flags & Class_::MODIFIER_PRIVATE) => Visibility::Private,
            default => null,
        };
    }
}
