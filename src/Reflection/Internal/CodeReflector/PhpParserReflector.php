<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\CodeReflector;

use PhpParser\Node;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
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
use PhpParser\NodeVisitorAbstract;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\ClassKind;
use Typhoon\Reflection\Internal\ConstantExpression\ConstantExpressionCompilerProvider;
use Typhoon\Reflection\Internal\ConstantExpression\Expression;
use Typhoon\Reflection\Internal\ConstantExpression\Values;
use Typhoon\Reflection\Internal\Data\Data;
use Typhoon\Reflection\Internal\Data\TraitMethodAlias;
use Typhoon\Reflection\Internal\Data\TypeData;
use Typhoon\Reflection\Internal\Data\Visibility;
use Typhoon\Reflection\Internal\DeclarationId\IdMap;
use Typhoon\Reflection\Internal\NativeAdapter\NativeTraitInfo;
use Typhoon\Reflection\Internal\NativeAdapter\NativeTraitInfoKey;
use Typhoon\Reflection\Internal\TypeContext\TypeContext;
use Typhoon\Reflection\Internal\TypeContext\TypeContextProvider;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;
use Typhoon\Type\Type;
use Typhoon\Type\types;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class PhpParserReflector extends NodeVisitorAbstract
{
    /**
     * @psalm-readonly-allow-private-mutation
     * @var IdMap<NamedFunctionId|NamedClassId|AnonymousClassId, TypedMap>
     */
    public IdMap $reflected;

    public function __construct(
        private readonly TypeContextProvider $typeContextProvider,
        private readonly ConstantExpressionCompilerProvider $constantExpressionCompilerProvider,
        private readonly TypedMap $baseData = new TypedMap(),
    ) {
        /** @var IdMap<NamedFunctionId|NamedClassId|AnonymousClassId, TypedMap> */
        $this->reflected = new IdMap();
    }

    public function leaveNode(Node $node): ?int
    {
        if ($node instanceof ClassLike) {
            $typeContext = $this->typeContextProvider->get();
            $id = $typeContext->id;
            \assert($id instanceof NamedClassId || $id instanceof AnonymousClassId);

            $data = $this->reflectClass($node, $typeContext);
            $this->reflected = $this->reflected->with($id, $data);

            return null;
        }

        if ($node instanceof Function_) {
            $typeContext = $this->typeContextProvider->get();
            $id = $typeContext->id;
            \assert($id instanceof NamedFunctionId);

            $data = $this
                ->baseData->withMap($this->reflectFunctionLike($node, $typeContext))
                ->with(Data::Namespace, $typeContext->namespace()?->toString() ?? '');
            $this->reflected = $this->reflected->with($id, $data);

            return null;
        }

        return null;
    }

    private function reflectClass(ClassLike $node, TypeContext $typeContext): TypedMap
    {
        $data = $this->baseData
            ->withMap($this->reflectNode($node))
            ->with(Data::Namespace, $typeContext->namespace()?->toString() ?? '')
            ->with(Data::TypeContext, $typeContext)
            ->with(Data::Attributes, $this->reflectAttributes($node->attrGroups));

        if ($node instanceof Class_) {
            return $data
                ->with(Data::ClassKind, ClassKind::Class_)
                ->with(Data::UnresolvedParent, $node->extends === null ? null : [$node->extends->toString(), []])
                ->with(Data::UnresolvedInterfaces, $this->reflectInterfaces($node->implements))
                ->withMap($this->reflectTraitUses($node->getTraitUses()))
                ->with(Data::Abstract, $node->isAbstract())
                ->with(Data::NativeReadonly, $node->isReadonly())
                ->with(Data::NativeFinal, $node->isFinal())
                ->with(Data::ClassConsts, $this->reflectConsts($typeContext, $node->getConstants()))
                ->with(Data::Properties, $this->reflectProperties($typeContext, $node->getProperties()))
                ->with(Data::Methods, $this->reflectMethods($node->getMethods()));
        }

        if ($node instanceof Interface_) {
            return $data
                ->with(Data::ClassKind, ClassKind::Interface)
                ->with(Data::UnresolvedInterfaces, $this->reflectInterfaces($node->extends))
                ->with(Data::ClassConsts, $this->reflectConsts($typeContext, $node->getConstants()))
                ->with(Data::Methods, $this->reflectMethods($node->getMethods()));
        }

        if ($node instanceof Enum_) {
            /** @var ?Type<int|string> */
            $backingType = $this->reflectType($typeContext, $node->scalarType);

            return $data
                ->with(Data::ClassKind, ClassKind::Enum)
                ->with(Data::UnresolvedInterfaces, $this->reflectInterfaces($node->implements))
                ->withMap($this->reflectTraitUses($node->getTraitUses()))
                ->with(Data::NativeFinal, true)
                ->with(Data::EnumBackingType, $backingType)
                ->with(Data::ClassConsts, [
                    ...$this->reflectConsts($typeContext, $node->getConstants()),
                    ...$this->reflectEnumCases($typeContext, array_filter(
                        $node->stmts,
                        static fn(Node $node): bool => $node instanceof EnumCase,
                    )),
                ])
                ->with(Data::Methods, $this->reflectMethods($node->getMethods()));
        }

        if ($node instanceof Trait_) {
            return $data
                ->with(Data::ClassKind, ClassKind::Trait)
                ->withMap($this->reflectTraitUses($node->getTraitUses()))
                ->with(Data::ClassConsts, $this->reflectConsts($typeContext, $node->getConstants()))
                ->with(Data::Properties, $this->reflectProperties($typeContext, $node->getProperties()))
                ->with(Data::Methods, $this->reflectMethods($node->getMethods()));
        }

        return $data;
    }

    private function reflectNode(Node $node): TypedMap
    {
        $data = new TypedMap();

        if ($node->getStartLine() > 0) {
            $data = $data->with(Data::StartLine, $node->getStartLine());

            if ($node->getEndLine() > 0) {
                $data = $data->with(Data::EndLine, $node->getEndLine());
            }
        }

        $docComment = $node->getDocComment();

        if ($docComment !== null && $docComment->getText() !== '') {
            $data = $data->with(Data::PhpDoc, $docComment->getText());

            if ($docComment->getStartLine() > 0) {
                $data = $data->with(Data::PhpDocStartLine, $docComment->getStartLine());
            }
        }

        return $data;
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
            $phpDoc = $node->getDocComment()?->getText() ?? '';

            if ($phpDoc !== '') {
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
     * @param array<AttributeGroup> $attributeGroups
     * @return list<TypedMap>
     */
    private function reflectAttributes(array $attributeGroups): array
    {
        $attributes = [];

        foreach ($attributeGroups as $attributeGroup) {
            foreach ($attributeGroup->attrs as $attr) {
                $attributes[] = $this->reflectNode($attr)
                    ->with(Data::AttributeClassName, $attr->name->toString())
                    ->with(Data::ArgumentExpressions, $this->reflectArguments($attr->args));
            }
        }

        return $attributes;
    }

    /**
     * @param array<Node\Arg> $nodes
     * @return array<Expression>
     */
    private function reflectArguments(array $nodes): array
    {
        $compiler = $this->constantExpressionCompilerProvider->get();
        $arguments = [];

        foreach ($nodes as $node) {
            if ($node->name === null) {
                $arguments[] = $compiler->compile($node->value);
            } else {
                $arguments[$node->name->name] = $compiler->compile($node->value);
            }
        }

        return $arguments;
    }

    /**
     * @param array<ClassConst> $nodes
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectConsts(TypeContext $typeContext, array $nodes): array
    {
        $compiler = $this->constantExpressionCompilerProvider->get();
        $consts = [];

        foreach ($nodes as $node) {
            $data = $this
                ->reflectNode($node)
                ->with(Data::Attributes, $this->reflectAttributes($node->attrGroups))
                ->with(Data::NativeFinal, $node->isFinal())
                ->with(Data::Type, new TypeData($this->reflectType($typeContext, $node->type)))
                ->with(Data::Visibility, $this->reflectVisibility($node->flags));

            foreach ($node->consts as $const) {
                $consts[$const->name->name] = $data->with(Data::ValueExpression, $compiler->compile($const->value));
            }
        }

        return $consts;
    }

    /**
     * @param array<EnumCase> $nodes
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectEnumCases(TypeContext $typeContext, array $nodes): array
    {
        \assert($typeContext->id instanceof NamedClassId, 'Enum cannot be an anonymous class');
        $enumType = types::object($typeContext->id);

        $compiler = $this->constantExpressionCompilerProvider->get();
        $cases = [];

        foreach ($nodes as $node) {
            $name = $node->name->name;
            $data = $this
                ->reflectNode($node)
                ->with(Data::Attributes, $this->reflectAttributes($node->attrGroups))
                ->with(Data::NativeFinal, false)
                ->with(Data::EnumCase, true)
                ->with(Data::Type, new TypeData(annotated: types::classConst($enumType, $name)))
                ->with(Data::Visibility, Visibility::Public);

            if ($node->expr !== null) {
                $data = $data->with(Data::EnumBackingValueExpression, $compiler->compile($node->expr));
            }

            $cases[$name] = $data;
        }

        return $cases;
    }

    /**
     * @param array<Property> $nodes
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectProperties(TypeContext $typeContext, array $nodes): array
    {
        $compiler = $this->constantExpressionCompilerProvider->get();
        $properties = [];

        foreach ($nodes as $node) {
            $data = $this
                ->reflectNode($node)
                ->with(Data::Attributes, $this->reflectAttributes($node->attrGroups))
                ->with(Data::Static, $node->isStatic())
                ->with(Data::NativeReadonly, $node->isReadonly())
                ->with(Data::Type, new TypeData($this->reflectType($typeContext, $node->type)))
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

    private function reflectFunctionLike(FunctionLike $node, ?TypeContext $typeContext = null): TypedMap
    {
        $typeContext ??= $this->typeContextProvider->get();

        return $this->reflectNode($node)
            ->with(Data::TypeContext, $typeContext)
            ->with(Data::Type, new TypeData($this->reflectType($typeContext, $node->getReturnType())))
            ->with(Data::ByReference, $node->returnsByRef())
            ->with(Data::Generator, IsGeneratorChecker::check($node))
            ->with(Data::Attributes, $this->reflectAttributes($node->getAttrGroups()))
            ->with(Data::Parameters, $this->reflectParameters($typeContext, $node->getParams()));
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
    private function reflectParameters(TypeContext $typeContext, array $nodes): array
    {
        $compiler = $this->constantExpressionCompilerProvider->get();
        $parameters = [];

        foreach ($nodes as $node) {
            \assert($node->var instanceof Variable && \is_string($node->var->name));
            $parameters[$node->var->name] = $this->reflectNode($node)
                ->with(Data::Visibility, $this->reflectVisibility($node->flags))
                ->with(Data::Attributes, $this->reflectAttributes($node->attrGroups))
                ->with(Data::Type, new TypeData($this->reflectType(
                    typeContext: $typeContext,
                    node: $node->type,
                    nullable: $node->default instanceof ConstFetch && $node->default->name->toCodeString() === 'null',
                )))
                ->with(Data::ByReference, $node->byRef)
                ->with(Data::DefaultValueExpression, $compiler->compile($node->default))
                ->with(Data::Promoted, $node->flags !== 0)
                ->with(Data::NativeReadonly, (bool) ($node->flags & Class_::MODIFIER_READONLY))
                ->with(Data::Variadic, $node->variadic);
        }

        return $parameters;
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

    /**
     * @return ($node is null ? null : Type)
     */
    private function reflectType(TypeContext $typeContext, null|Name|Identifier|ComplexType $node, bool $nullable = false): ?Type
    {
        if ($node === null) {
            return null;
        }

        if ($nullable) {
            return types::nullable($this->reflectType($typeContext, $node));
        }

        if ($node instanceof NullableType) {
            return types::nullable($this->reflectType($typeContext, $node->type));
        }

        if ($node instanceof UnionType) {
            return types::union(...array_map(
                fn(Node $child): Type => $this->reflectType($typeContext, $child),
                $node->types,
            ));
        }

        if ($node instanceof IntersectionType) {
            return types::intersection(...array_map(
                fn(Node $child): Type => $this->reflectType($typeContext, $child),
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
                default => throw new \LogicException(sprintf('Native type "%s" is not supported', $node->name)),
            };
        }

        if ($node instanceof Name) {
            return $typeContext->resolveType($node);
        }

        /** @psalm-suppress MixedArgument */
        throw new \LogicException(sprintf('Type node of class %s is not supported', $node::class));
    }
}
