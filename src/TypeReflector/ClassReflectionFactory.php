<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\TypeReflector;

use ExtendedTypeSystem\ClassLikeTypeReflection;
use ExtendedTypeSystem\MethodTypeReflection;
use ExtendedTypeSystem\TemplateReflection;
use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\TypeReflector;
use ExtendedTypeSystem\Variance;
use PhpParser\NameContext;
use PhpParser\Node\Expr\Variable as VariableNode;
use PhpParser\Node\Name;
use PhpParser\Node\Param as ParameterNode;
use PhpParser\Node\Stmt\Class_ as ClassNode;
use PhpParser\Node\Stmt\ClassLike as ClassLikeNode;
use PhpParser\Node\Stmt\ClassMethod as MethodNode;
use PhpParser\Node\Stmt\Enum_ as EnumNode;
use PhpParser\Node\Stmt\Interface_ as InterfaceNode;
use PhpParser\Node\Stmt\Property as PropertyNode;
use PhpParser\Node\Stmt\Trait_ as TraitNode;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem
 */
final class ClassReflectionFactory
{
    public function __construct(
        private readonly PHPDocParser $phpDocParser,
        private readonly TypeResolver $typeResolver = new TypeResolver(),
    ) {
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return ClassLikeTypeReflection<T>
     */
    public function build(TypeReflector $typeReflector, string $class, NameContext $nameContext, ClassLikeNode $node): ClassLikeTypeReflection
    {
        $parent = $node instanceof ClassNode ? TypeResolver::nameToClassString($node->extends) : null;
        $phpDoc = $this->phpDocParser->parseNode($node);
        $classScope = new ClassLikeScope(
            nameContext: clone $nameContext,
            name: $class,
            parent: $parent,
            final: $node instanceof ClassNode && $node->isFinal(),
            templateNames: $phpDoc->templateNames(),
        );
        $methods = $this->buildMethods($classScope, $node->getMethods());

        if ($node instanceof ClassNode) {
            return new ClassLikeTypeReflection(
                typeReflector: $typeReflector,
                name: $class,
                parentClass: $parent,
                templates: $this->buildTemplates($phpDoc, $classScope),
                parentTemplateArguments: $node->extends === null ? null : $this->buildExtendsTemplateArguments($phpDoc, $classScope, [$node->extends])[$parent] ?? [],
                interfacesTemplateArguments: $this->buildImplementsTemplateArguments($phpDoc, $classScope, $node->implements),
                propertyTypes: $this->buildPropertyTypes($classScope, $node->getProperties(), $node->getMethod('__construct'), $methods['__construct'] ?? null),
                methods: $methods,
            );
        }

        if ($node instanceof InterfaceNode) {
            return new ClassLikeTypeReflection(
                typeReflector: $typeReflector,
                name: $class,
                templates: $this->buildTemplates($phpDoc, $classScope),
                interfacesTemplateArguments: $this->buildExtendsTemplateArguments($phpDoc, $classScope, $node->extends),
                methods: $methods,
            );
        }

        if ($node instanceof EnumNode) {
            return new ClassLikeTypeReflection(
                typeReflector: $typeReflector,
                name: $class,
                templates: $this->buildTemplates($phpDoc, $classScope),
                interfacesTemplateArguments: $this->buildExtendsTemplateArguments($phpDoc, $classScope, $node->implements),
                methods: $methods,
            );
        }

        if ($node instanceof TraitNode) {
            return new ClassLikeTypeReflection(
                typeReflector: $typeReflector,
                name: $class,
                templates: $this->buildTemplates($phpDoc, $classScope),
                propertyTypes: $this->buildPropertyTypes($classScope, $node->getProperties(), $node->getMethod('__construct'), $methods['__construct'] ?? null),
                methods: $methods,
            );
        }

        throw new \LogicException(sprintf('%s is not supported.', $node::class));
    }

    /**
     * @param array<PropertyNode> $nodes
     * @return array<non-empty-string, Type>
     */
    private function buildPropertyTypes(ClassLikeScope $classScope, array $nodes, ?MethodNode $constructorNode, ?MethodTypeReflection $constructorReflection): array
    {
        $staticScope = null;
        $instanceScope = null;
        $types = [];

        foreach ($nodes as $node) {
            if ($node->isStatic()) {
                $scope = $staticScope ??= new PropertyScope($classScope, true);
            } else {
                $scope = $instanceScope ??= new PropertyScope($classScope, false);
            }

            $phpDoc = $this->phpDocParser->parseNode($node);
            $type = $this->typeResolver->resolveTypeNode($scope, $phpDoc->varType() ?? $node->type);

            foreach ($node->props as $property) {
                /** @var non-empty-string $property->name->name */
                $types[$property->name->name] = $type;
            }
        }

        if ($constructorNode === null || $constructorReflection === null) {
            return $types;
        }

        foreach ($constructorNode->params as $node) {
            if ($node->flags & ClassNode::MODIFIER_PUBLIC || $node->flags & ClassNode::MODIFIER_PROTECTED || $node->flags & ClassNode::MODIFIER_PRIVATE) {
                /**
                 * @var VariableNode $node->var
                 * @var non-empty-string $node->var->name
                 */
                $types[$node->var->name] = $constructorReflection->parameterType($node->var->name);
            }
        }

        return $types;
    }

    /**
     * @param array<MethodNode> $nodes
     * @return array<non-empty-string, MethodTypeReflection>
     */
    private function buildMethods(ClassLikeScope $classScope, array $nodes): array
    {
        $methods = [];

        foreach ($nodes as $node) {
            /** @var non-empty-string */
            $name = $node->name->toString();
            $phpDoc = $this->phpDocParser->parseNode($node);
            $scope = new MethodScope($classScope, $name, $node->isStatic(), $phpDoc->templateNames());

            $methods[$name] = new MethodTypeReflection(
                class: $scope->self(),
                name: $name,
                templates: $this->buildTemplates($phpDoc, $scope),
                parameterTypes: $this->buildParameterTypes($phpDoc, $scope, $node->params),
                returnType: $this->typeResolver->resolveTypeNode($scope, $phpDoc->returnType() ?? $node->returnType),
            );
        }

        return $methods;
    }

    /**
     * @param array<ParameterNode> $nodes
     * @return array<non-empty-string, Type>
     */
    private function buildParameterTypes(PHPDoc $phpDoc, Scope $scope, array $nodes): array
    {
        $types = [];

        foreach ($nodes as $node) {
            \assert($node->var instanceof VariableNode && \is_string($node->var->name));
            /** @var non-empty-string $node->var->name */
            $types[$node->var->name] = $this->typeResolver->resolveTypeNode($scope, $phpDoc->paramType($node->var->name) ?? $node->type);
        }

        return $types;
    }

    /**
     * @param array<Name> $classes
     * @return array<class-string, list<Type>>
     */
    private function buildExtendsTemplateArguments(PHPDoc $phpDoc, Scope $scope, array $classes): array
    {
        if ($classes === []) {
            return [];
        }

        $extendsTemplateArguments = array_fill_keys(array_map(TypeResolver::nameToClassString(...), $classes), []);

        foreach ($phpDoc->extendsTypes() as $typeNode) {
            $type = $this->typeResolver->resolveTypeNode($scope, $typeNode);
            \assert($type instanceof Type\NamedObjectType);

            if (isset($extendsTemplateArguments[$type->class])) {
                $extendsTemplateArguments[$type->class] = $type->templateArguments;
            }
        }

        return $extendsTemplateArguments;
    }

    /**
     * @param array<Name> $classes
     * @return array<class-string, list<Type>>
     */
    private function buildImplementsTemplateArguments(PHPDoc $phpDoc, Scope $scope, array $classes): array
    {
        if ($classes === []) {
            return [];
        }

        $implementsTemplateArguments = array_fill_keys(array_map(TypeResolver::nameToClassString(...), $classes), []);

        foreach ($phpDoc->implementsTypes() as $typeNode) {
            $type = $this->typeResolver->resolveTypeNode($scope, $typeNode);
            \assert($type instanceof Type\NamedObjectType);

            if (isset($implementsTemplateArguments[$type->class])) {
                $implementsTemplateArguments[$type->class] = $type->templateArguments;
            }
        }

        return $implementsTemplateArguments;
    }

    /**
     * @return array<non-empty-string, TemplateReflection>
     */
    private function buildTemplates(PHPDoc $phpDoc, Scope $scope): array
    {
        $templates = [];
        $index = 0;

        foreach ($phpDoc->templates() as $tagName => $tagValue) {
            /** @var non-empty-string $tagValue->name */
            $templates[$tagValue->name] = new TemplateReflection(
                index: $index++,
                name: $tagValue->name,
                constraint: $this->typeResolver->resolveTypeNode($scope, $tagValue->bound),
                variance: match (true) {
                    str_ends_with($tagName, 'covariant') => Variance::COVARIANT,
                    str_ends_with($tagName, 'contravariant') => Variance::CONTRAVARIANT,
                    default => Variance::INVARIANT,
                },
            );
        }

        return $templates;
    }
}
