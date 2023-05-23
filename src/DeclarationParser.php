<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

use ExtendedTypeSystem\ClassLocator\LoadedClassLocator;
use ExtendedTypeSystem\DeclarationParser\ClassLikeTypeScope;
use ExtendedTypeSystem\DeclarationParser\FindClassVisitor;
use ExtendedTypeSystem\DeclarationParser\MethodTypeScope;
use ExtendedTypeSystem\DeclarationParser\PHPDoc;
use ExtendedTypeSystem\DeclarationParser\PHPDocParser;
use ExtendedTypeSystem\DeclarationParser\PropertyTypeScope;
use ExtendedTypeSystem\DeclarationParser\TypeParser;
use ExtendedTypeSystem\DeclarationParser\TypeScope;
use ExtendedTypeSystem\TagPrioritizer\PHPStanOverPsalmOverOthersTagPrioritizer;
use PhpParser\Lexer\Emulative;
use PhpParser\NameContext;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Expr\Variable as VariableNode;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param as ParameterNode;
use PhpParser\Node\Stmt\Class_ as ClassNode;
use PhpParser\Node\Stmt\ClassMethod as MethodNode;
use PhpParser\Node\Stmt\Enum_ as EnumNode;
use PhpParser\Node\Stmt\Interface_ as InterfaceNode;
use PhpParser\Node\Stmt\Property as PropertyNode;
use PhpParser\Node\Stmt\Trait_ as TraitNode;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\Parser\Php7;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer as PHPStanPhpDocLexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser as PHPStanPhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser as PHPStanTypeParser;

/**
 * @psalm-api
 */
final class DeclarationParser
{
    private readonly TypeParser $typeParser;
    private readonly PHPDocParser $phpDocParser;

    public function __construct(
        private readonly ClassLocator $classLocator = new LoadedClassLocator(),
        private readonly Parser $phpParser = new Php7(new Emulative(['usedAttributes' => ['comments']])),
        PHPStanPhpDocParser $phpDocParser = new PHPStanPhpDocParser(new PHPStanTypeParser(new ConstExprParser()), new ConstExprParser()),
        PHPStanPhpDocLexer $phpDocLexer = new PHPStanPhpDocLexer(),
        TagPrioritizer $tagPrioritizer = new PHPStanOverPsalmOverOthersTagPrioritizer(),
    ) {
        $this->typeParser = new TypeParser();
        $this->phpDocParser = new PHPDocParser(
            parser: $phpDocParser,
            lexer: $phpDocLexer,
            tagPrioritizer: $tagPrioritizer,
        );
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return ClassDeclaration<T>|InterfaceDeclaration<T>|EnumDeclaration<T>|TraitDeclaration<T>
     */
    public function parseClassLike(string $class): ClassDeclaration|InterfaceDeclaration|EnumDeclaration|TraitDeclaration
    {
        $source = $this->classLocator->locateClass($class);

        if ($source === null) {
            throw new \LogicException(sprintf('Failed to locate class %s.', $class));
        }

        $statements = $this->phpParser->parse($source->code) ?? [];
        $traverser = new NodeTraverser();
        $nameResolver = new NameResolver();
        $findClassVisitor = new FindClassVisitor($class);
        $traverser->addVisitor($nameResolver);
        $traverser->addVisitor($findClassVisitor);
        $traverser->traverse($statements);
        $node = $findClassVisitor->node;

        if ($node === null) {
            throw new \LogicException(sprintf('Class %s was not found in %s.', $class, $source->description));
        }

        $nameContext = $nameResolver->getNameContext();

        if ($node instanceof ClassNode) {
            return $this->parseClassNode($class, $node, $nameContext);
        }

        if ($node instanceof InterfaceNode) {
            return $this->parseInterfaceNode($class, $node, $nameContext);
        }

        if ($node instanceof EnumNode) {
            return $this->parseEnumNode($class, $node, $nameContext);
        }

        if ($node instanceof TraitNode) {
            return $this->parseTraitNode($class, $node, $nameContext);
        }

        throw new \LogicException(sprintf('Node %s is not supported.', $node::class));
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return ClassDeclaration<T>
     */
    private function parseClassNode(string $class, ClassNode $node, NameContext $nameContext): ClassDeclaration
    {
        $phpDoc = $this->phpDocParser->parseNode($node);

        $parent = TypeParser::nameToClassString($node->extends);
        $scope = new ClassLikeTypeScope(
            nameContext: $nameContext,
            name: $class,
            parent: $parent,
            final: $node->isFinal(),
            templateNames: $phpDoc->templateNames(),
        );
        $methods = $this->parseMethods($scope, $node->getMethods());

        return new ClassDeclaration(
            name: $class,
            templates: $this->parseTemplates($phpDoc, $scope),
            parent: $parent,
            parentTemplateArguments: $this->parseParentTemplateArguments($phpDoc, $scope, $parent),
            interfacesTemplateArguments: $this->parseImplementsTemplateArguments($phpDoc, $scope, $node->implements),
            traitsTemplateArguments: [],
            constantTypes: [],
            propertyTypes: $this->parsePropertyTypes(
                classScope: $scope,
                nodes: $node->getProperties(),
                constructorNode: $node->getMethod('__construct'),
                constructorDeclaration: $methods['__construct'] ?? null,
            ),
            methods: $methods,
        );
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return InterfaceDeclaration<T>
     */
    private function parseInterfaceNode(string $class, InterfaceNode $node, NameContext $nameContext): InterfaceDeclaration
    {
        $phpDoc = $this->phpDocParser->parseNode($node);

        $scope = new ClassLikeTypeScope(
            nameContext: $nameContext,
            name: $class,
            parent: null,
            final: false,
            templateNames: $phpDoc->templateNames(),
        );

        return new InterfaceDeclaration(
            name: $class,
            templates: $this->parseTemplates($phpDoc, $scope),
            interfacesTemplateArguments: $this->parseExtendsTemplateArguments($phpDoc, $scope, $node->extends),
            constantTypes: [],
            methods: $this->parseMethods($scope, $node->getMethods()),
        );
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return EnumDeclaration<T>
     */
    private function parseEnumNode(string $class, EnumNode $node, NameContext $nameContext): EnumDeclaration
    {
        $phpDoc = $this->phpDocParser->parseNode($node);

        $scope = new ClassLikeTypeScope(
            nameContext: $nameContext,
            name: $class,
            parent: null,
            final: true,
            templateNames: $phpDoc->templateNames(),
        );

        $properties = ['name' => new TypeDeclaration(types::nonEmptyString)];

        if ($node->scalarType !== null) {
            $properties['value'] = new TypeDeclaration($this->typeParser->parseNativeTypeNode($scope, $node->scalarType));
        }

        return new EnumDeclaration(
            name: $class,
            interfacesTemplateArguments: $this->parseImplementsTemplateArguments($phpDoc, $scope, $node->implements),
            traitsTemplateArguments: [],
            constantTypes: [],
            propertyTypes: $properties,
            methods: $this->parseMethods($scope, $node->getMethods()),
        );
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return TraitDeclaration<T>
     */
    private function parseTraitNode(string $class, TraitNode $node, NameContext $nameContext): TraitDeclaration
    {
        $phpDoc = $this->phpDocParser->parseNode($node);

        $scope = new ClassLikeTypeScope(
            nameContext: $nameContext,
            name: $class,
            parent: null,
            final: false,
            templateNames: $phpDoc->templateNames(),
        );
        $methods = $this->parseMethods($scope, $node->getMethods());

        return new TraitDeclaration(
            name: $class,
            templates: $this->parseTemplates($phpDoc, $scope),
            traitsTemplateArguments: [],
            propertyTypes: $this->parsePropertyTypes(
                classScope: $scope,
                nodes: $node->getProperties(),
                constructorNode: $node->getMethod('__construct'),
                constructorDeclaration: $methods['__construct'] ?? null,
            ),
            methods: $methods,
        );
    }

    /**
     * @return array<non-empty-string, TemplateDeclaration>
     */
    private function parseTemplates(PHPDoc $phpDoc, TypeScope $scope): array
    {
        $templates = [];
        $index = 0;

        foreach ($phpDoc->templates() as $tagName => $tagValue) {
            /** @var non-empty-string $tagValue->name */
            $templates[$tagValue->name] = new TemplateDeclaration(
                index: $index++,
                name: $tagValue->name,
                constraint: $this->typeParser->parsePHPDocTypeNode($scope, $tagValue->bound) ?? types::mixed,
                variance: match (true) {
                    str_ends_with($tagName, 'covariant') => Variance::COVARIANT,
                    str_ends_with($tagName, 'contravariant') => Variance::CONTRAVARIANT,
                    default => Variance::INVARIANT,
                },
            );
        }

        return $templates;
    }

    /**
     * @param ?class-string $parent
     * @return list<Type>
     */
    private function parseParentTemplateArguments(PHPDoc $phpDoc, TypeScope $scope, ?string $parent): array
    {
        if ($parent === null) {
            return [];
        }

        foreach ($phpDoc->extendsTypes() as $typeNode) {
            $type = $this->typeParser->parsePHPDocTypeNode($scope, $typeNode);
            \assert($type instanceof Type\NamedObjectType);

            if ($type->class === $parent) {
                return $type->templateArguments;
            }
        }

        return [];
    }

    /**
     * @param array<Name> $classes
     * @return array<class-string, list<Type>>
     */
    private function parseExtendsTemplateArguments(PHPDoc $phpDoc, TypeScope $scope, array $classes): array
    {
        if ($classes === []) {
            return [];
        }

        $extendsTemplateArguments = array_fill_keys(array_map(TypeParser::nameToClassString(...), $classes), []);

        foreach ($phpDoc->extendsTypes() as $typeNode) {
            $type = $this->typeParser->parsePHPDocTypeNode($scope, $typeNode);
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
    private function parseImplementsTemplateArguments(PHPDoc $phpDoc, TypeScope $scope, array $classes): array
    {
        if ($classes === []) {
            return [];
        }

        $implementsTemplateArguments = array_fill_keys(array_map(TypeParser::nameToClassString(...), $classes), []);

        foreach ($phpDoc->implementsTypes() as $typeNode) {
            $type = $this->typeParser->parsePHPDocTypeNode($scope, $typeNode);
            \assert($type instanceof Type\NamedObjectType);

            if (isset($implementsTemplateArguments[$type->class])) {
                $implementsTemplateArguments[$type->class] = $type->templateArguments;
            }
        }

        return $implementsTemplateArguments;
    }

    /**
     * @param array<PropertyNode> $nodes
     * @return array<non-empty-string, TypeDeclaration>
     */
    private function parsePropertyTypes(ClassLikeTypeScope $classScope, array $nodes, ?MethodNode $constructorNode, ?MethodDeclaration $constructorDeclaration): array
    {
        $staticScope = null;
        $instanceScope = null;
        $types = [];

        foreach ($nodes as $node) {
            if ($node->isStatic()) {
                $scope = $staticScope ??= new PropertyTypeScope($classScope, true);
            } else {
                $scope = $instanceScope ??= new PropertyTypeScope($classScope, false);
            }

            $phpDoc = $this->phpDocParser->parseNode($node);
            $type = $this->parseTypeDeclaration($scope, $node->type, $phpDoc->varType());

            foreach ($node->props as $property) {
                /** @var non-empty-string $property->name->name */
                $types[$property->name->name] = $type;
            }
        }

        if ($constructorNode === null || $constructorDeclaration === null) {
            return $types;
        }

        foreach ($constructorNode->params as $node) {
            if ($this->isParamNodePromoted($node)) {
                /**
                 * @var VariableNode $node->var
                 * @var non-empty-string $node->var->name
                 */
                $types[$node->var->name] = $constructorDeclaration->parameterTypes[$node->var->name];
            }
        }

        return $types;
    }

    /**
     * @param array<MethodNode> $nodes
     * @return array<non-empty-string, MethodDeclaration>
     */
    private function parseMethods(ClassLikeTypeScope $classScope, array $nodes): array
    {
        $methods = [];

        foreach ($nodes as $node) {
            /** @var non-empty-string */
            $name = $node->name->toString();
            $phpDoc = $this->phpDocParser->parseNode($node);
            $scope = new MethodTypeScope($classScope, $name, $node->isStatic(), $phpDoc->templateNames());

            $methods[$name] = new MethodDeclaration(
                name: $name,
                templates: $this->parseTemplates($phpDoc, $scope),
                parameterTypes: $this->parseParameterTypes($phpDoc, $scope, $node->params),
                returnType: $this->parseTypeDeclaration($scope, $node->returnType, $phpDoc->returnType()),
            );
        }

        return $methods;
    }

    /**
     * @param array<ParameterNode> $nodes
     * @return array<non-empty-string, TypeDeclaration>
     */
    private function parseParameterTypes(PHPDoc $phpDoc, TypeScope $scope, array $nodes): array
    {
        $types = [];

        foreach ($nodes as $node) {
            \assert($node->var instanceof VariableNode && \is_string($node->var->name));
            /** @var non-empty-string $node->var->name */
            $types[$node->var->name] = $this->parseTypeDeclaration($scope, $node->type, $phpDoc->paramType($node->var->name));
        }

        return $types;
    }

    private function parseTypeDeclaration(TypeScope $scope, null|Identifier|Name|ComplexType $nativeTypeNode, ?TypeNode $phpDocTypeNode): TypeDeclaration
    {
        return new TypeDeclaration(
            nativeType: $this->typeParser->parseNativeTypeNode($scope, $nativeTypeNode),
            phpDocType: $this->typeParser->parsePHPDocTypeNode($scope, $phpDocTypeNode),
        );
    }

    private function isParamNodePromoted(ParameterNode $node): bool
    {
        return $node->flags & ClassNode::MODIFIER_PUBLIC || $node->flags & ClassNode::MODIFIER_PROTECTED || $node->flags & ClassNode::MODIFIER_PRIVATE;
    }
}
