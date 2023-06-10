<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\Reflector;

use ExtendedTypeSystem\Reflection\ClassReflection;
use ExtendedTypeSystem\Reflection\MethodReflection;
use ExtendedTypeSystem\Reflection\NameContext;
use ExtendedTypeSystem\Reflection\ParameterReflection;
use ExtendedTypeSystem\Reflection\PhpDocParser\PhpDoc;
use ExtendedTypeSystem\Reflection\PhpDocParser\PhpDocParser;
use ExtendedTypeSystem\Reflection\PropertyReflection;
use ExtendedTypeSystem\Reflection\Reflector;
use ExtendedTypeSystem\Reflection\TemplateReflection;
use ExtendedTypeSystem\Reflection\TypeReflection;
use ExtendedTypeSystem\Reflection\Variance;
use ExtendedTypeSystem\Reflection\Visibility;
use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\types;
use PhpParser\Lexer\Emulative;
use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\Parser\Php7;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

final class PhpParserReflector
{
    public function __construct(
        private readonly Parser $phpParser = new Php7(new Emulative(['usedAttributes' => ['comments']])),
        private readonly PhpDocParser $phpDocParser = new PhpDocParser(),
        private readonly PhpDocTypeReflector $phpDocTypeReflector = new PhpDocTypeReflector(),
    ) {
    }

    public function parseCode(string $code, Reflector $reflector): Reflections
    {
        $nodes = $this->phpParser->parse($code) ?? throw new \RuntimeException();
        $reflections = new Reflections();

        if ($nodes === []) {
            return $reflections;
        }

        $nameContext = new NameContext();
        $nameContextVisitor = new NameContextVisitor(
            phpDocParser: $this->phpDocParser,
            nameContext: $nameContext,
        );
        $discoveringVisitor = new DiscoveringVisitor(
            phpParserReflector: $this,
            reflector: $reflector,
            nameContext: $nameContext,
            reflections: $reflections,
        );

        $traverser = new NodeTraverser();
        $traverser->addVisitor($nameContextVisitor);
        $traverser->addVisitor($discoveringVisitor);
        $traverser->traverse($nodes);

        return $reflections;
    }

    public function reflectClass(ClassLike $node, NameContext $nameContext, Reflector $reflector): ClassReflection
    {
        if ($node->name === null) {
            throw new \LogicException();
        }

        /** @var class-string */
        $name = $nameContext->resolveNameAsClass($node->name->toString());
        $phpDoc = $this->phpDocParser->parseNodePhpDoc($node);
        $readonly = $node instanceof Class_ && $node->isReadonly();

        return new ClassReflection(
            name: $name,
            templates: $this->reflectTemplates($phpDoc, $nameContext, $reflector),
            final: $node instanceof Class_ && $node->isFinal(),
            abstract: $node instanceof Class_ && $node->isAbstract(),
            readonly: $readonly,
            parent: $this->reflectParent($node, $phpDoc, $nameContext, $reflector),
            interfaces: $this->reflectInterfaces($node, $phpDoc, $nameContext, $reflector),
            ownProperties: $this->reflectOwnProperties(
                nodes: $node->getProperties(),
                constructorNode: $node->getMethod('__construct'),
                readonlyClass: $readonly,
                nameContext: $nameContext,
                reflector: $reflector,
            ),
            ownMethods: $this->reflectOwnMethods(
                nodes: $node->getMethods(),
                interface: $node instanceof Interface_,
                nameContext: $nameContext,
                reflector: $reflector,
            ),
        );
    }

    private function reflectParent(ClassLike $node, PhpDoc $phpDoc, NameContext $nameContext, Reflector $reflector): ?Type\NamedObjectType
    {
        if (!$node instanceof Class_ || $node->extends === null) {
            return null;
        }

        /** @var class-string */
        $parentClass = $nameContext->resolveNameAsClass($node->extends->toString());

        foreach ($phpDoc->extendedTypes as $phpDocExtendedType) {
            /** @var Type\NamedObjectType $extendedType */
            $extendedType = $this->phpDocTypeReflector->reflectType($phpDocExtendedType, $nameContext, $reflector);

            if ($extendedType->class === $parentClass) {
                return $extendedType;
            }
        }

        return types::object($parentClass);
    }

    /**
     * @return list<Type\NamedObjectType>
     */
    private function reflectInterfaces(ClassLike $node, PhpDoc $phpDoc, NameContext $nameContext, Reflector $reflector): array
    {
        if ($node instanceof Interface_) {
            $interfaceNames = $node->extends;
            $phpDocImplementedTypes = $phpDoc->extendedTypes;
        } elseif ($node instanceof Class_ || $node instanceof Enum_) {
            $interfaceNames = $node->implements;
            $phpDocImplementedTypes = $phpDoc->implementedTypes;
        } else {
            return [];
        }

        if ($interfaceNames === []) {
            return [];
        }

        $phpDocInterfaceTypesByClass = [];

        foreach ($phpDocImplementedTypes as $phpDocImplementedType) {
            /** @var Type\NamedObjectType $implementedType */
            $implementedType = $this->phpDocTypeReflector->reflectType($phpDocImplementedType, $nameContext, $reflector);
            $phpDocInterfaceTypesByClass[$implementedType->class] = $implementedType;
        }

        return array_values(
            array_map(
                static function (Node\Name $interfaceName) use ($nameContext, $phpDocInterfaceTypesByClass): Type\NamedObjectType {
                    /** @var class-string */
                    $interface = $nameContext->resolveNameAsClass($interfaceName->toString());

                    return $phpDocInterfaceTypesByClass[$interface] ?? types::object($interface);
                },
                $interfaceNames,
            ),
        );
    }

    /**
     * @param array<Property> $nodes
     * @return array<non-empty-string, PropertyReflection>
     */
    private function reflectOwnProperties(array $nodes, ?ClassMethod $constructorNode, bool $readonlyClass, NameContext $nameContext, Reflector $reflector): array
    {
        $properties = [];

        foreach ($nodes as $node) {
            $phpDoc = $this->phpDocParser->parseNodePhpDoc($node);
            $type = $this->reflectType($node->type, $phpDoc->varType, $nameContext, $reflector);

            foreach ($node->props as $property) {
                $name = $property->name->name;
                $properties[$name] = new PropertyReflection(
                    name: $name,
                    static: $node->isStatic(),
                    promoted: false,
                    hasDefaultValue: $property->default !== null,
                    readonly: $node->isReadonly() || $readonlyClass,
                    visibility: $this->reflectVisibility($node),
                    type: $type,
                );
            }
        }

        if ($constructorNode === null) {
            return $properties;
        }

        $phpDoc = $this->phpDocParser->parseNodePhpDoc($constructorNode);

        foreach ($constructorNode->params as $node) {
            $visibility = $this->reflectParameterVisibility($node);

            if ($visibility === null) {
                continue;
            }

            \assert($node->var instanceof Variable && \is_string($node->var->name));
            $name = $node->var->name;
            $properties[$name] = new PropertyReflection(
                name: $name,
                static: false,
                promoted: true,
                hasDefaultValue: $node->default !== null,
                readonly: $readonlyClass || ($node->flags & Class_::MODIFIER_READONLY) !== 0,
                visibility: $visibility,
                type: $this->reflectType($node->type, $phpDoc->paramTypes[$name] ?? null, $nameContext, $reflector),
            );
        }

        return $properties;
    }

    /**
     * @param array<ClassMethod> $nodes
     * @return array<non-empty-string, MethodReflection>
     */
    private function reflectOwnMethods(array $nodes, bool $interface, NameContext $nameContext, Reflector $reflector): array
    {
        $methods = [];

        foreach ($nodes as $node) {
            $name = $node->name->name;
            $phpDoc = $this->phpDocParser->parseNodePhpDoc($node);

            try {
                $nameContext->enterMethod($name, array_keys($phpDoc->templates));

                $methods[$name] = new MethodReflection(
                    name: $name,
                    static: $node->isStatic(),
                    final: $node->isFinal(),
                    abstract: $node->isAbstract() || $interface,
                    visibility: $this->reflectVisibility($node),
                    templates: $this->reflectTemplates($phpDoc, $nameContext, $reflector),
                    parameters: $this->reflectParameters($node->params, $phpDoc, $nameContext, $reflector),
                    returnType: $this->reflectType($node->returnType, $phpDoc->returnType, $nameContext, $reflector),
                );
            } finally {
                $nameContext->leaveMethod();
            }
        }

        return $methods;
    }

    /**
     * @param array<Param> $nodes
     * @return array<non-empty-string, ParameterReflection>
     */
    private function reflectParameters(array $nodes, PhpDoc $phpDoc, NameContext $nameContext, Reflector $reflector): array
    {
        $parameters = [];

        foreach (array_values($nodes) as $position => $node) {
            \assert($node->var instanceof Variable && \is_string($node->var->name));
            $name = $node->var->name;
            $parameters[$name] = new ParameterReflection(
                position: $position,
                name: $name,
                promoted: $this->reflectParameterVisibility($node) !== null,
                variadic: $node->variadic,
                hasDefaultValue: $node->default !== null,
                type: $this->reflectType($node->type, $phpDoc->paramTypes[$name] ?? null, $nameContext, $reflector),
            );
        }

        return $parameters;
    }

    /**
     * @return array<non-empty-string, TemplateReflection>
     */
    private function reflectTemplates(PhpDoc $phpDoc, NameContext $nameContext, Reflector $reflector): array
    {
        $templates = [];

        foreach (array_values($phpDoc->templates) as $position => $template) {
            $variance = $template->getAttribute('variance');
            $templates[$template->name] = new TemplateReflection(
                position: $position,
                name: $template->name,
                constraint: $this->phpDocTypeReflector->reflectType($template->bound, $nameContext, $reflector) ?? types::mixed,
                variance: $variance instanceof Variance ? $variance : Variance::INVARIANT,
            );
        }

        return $templates;
    }

    private function reflectType(?Node $native, ?TypeNode $phpDoc, NameContext $nameContext, Reflector $reflector): TypeReflection
    {
        return new TypeReflection(
            native: $this->reflectNativeType($native, $nameContext, $reflector),
            phpDoc: $this->phpDocTypeReflector->reflectType($phpDoc, $nameContext, $reflector),
        );
    }

    /**
     * @return ($node is null ? null : Type)
     */
    private function reflectNativeType(?Node $node, NameContext $nameContext, Reflector $reflector): ?Type
    {
        if ($node === null) {
            return null;
        }

        if ($node instanceof Node\NullableType) {
            return types::nullable($this->reflectNativeType($node->type, $nameContext, $reflector));
        }

        if ($node instanceof Node\UnionType) {
            return types::union(...array_map(
                fn (Node $child): Type => $this->reflectNativeType($child, $nameContext, $reflector),
                $node->types,
            ));
        }

        if ($node instanceof Node\IntersectionType) {
            return types::intersection(...array_map(
                fn (Node $child): Type => $this->reflectNativeType($child, $nameContext, $reflector),
                $node->types,
            ));
        }

        if ($node instanceof Node\Identifier) {
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
                'array' => types::array(),
                'object' => types::object,
                'callable' => types::callable(),
                'resource' => types::resource,
                'mixed' => types::mixed,
                default => throw new \LogicException(),
            };
        }

        if ($node instanceof Node\Name) {
            return $nameContext->resolveName($node->toString(), new TypeNameResolver($reflector));
        }

        throw new \LogicException();
    }

    private function reflectVisibility(Property|ClassMethod $node): Visibility
    {
        return match (true) {
            $node->isProtected() => Visibility::PROTECTED,
            $node->isPrivate() => Visibility::PRIVATE,
            default => Visibility::PUBLIC,
        };
    }

    private function reflectParameterVisibility(Param $node): ?Visibility
    {
        return match (1) {
            $node->flags & Class_::MODIFIER_PUBLIC => Visibility::PUBLIC,
            $node->flags & Class_::MODIFIER_PROTECTED => Visibility::PROTECTED,
            $node->flags & Class_::MODIFIER_PRIVATE => Visibility::PRIVATE,
            default => null,
        };
    }
}
