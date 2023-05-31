<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection;

use ExtendedTypeSystem\Reflection\ClassLocator\LoadedClassLocator;
use ExtendedTypeSystem\Reflection\PHPDocParser\PHPDoc;
use ExtendedTypeSystem\Reflection\PHPDocParser\PHPDocParser;
use ExtendedTypeSystem\Reflection\TagPrioritizer\PHPStanOverPsalmOverOthersTagPrioritizer;
use ExtendedTypeSystem\Reflection\TypeParser\ClassLikeScope;
use ExtendedTypeSystem\Reflection\TypeParser\MethodScope;
use ExtendedTypeSystem\Reflection\TypeParser\PropertyScope;
use ExtendedTypeSystem\Reflection\TypeParser\Scope;
use ExtendedTypeSystem\Reflection\TypeParser\TypeParser;
use ExtendedTypeSystem\Reflection\TypeReflector\ClassLikeReflectionBuilder;
use ExtendedTypeSystem\Reflection\TypeReflector\FindClassVisitor;
use ExtendedTypeSystem\Type\NamedObjectType;
use ExtendedTypeSystem\types;
use PhpParser\Lexer\Emulative;
use PhpParser\NameContext;
use PhpParser\Node\Expr\Variable as VariableNode;
use PhpParser\Node\Name as NameNode;
use PhpParser\Node\Param as ParameterNode;
use PhpParser\Node\Stmt\Class_ as ClassNode;
use PhpParser\Node\Stmt\ClassLike as ClassLikeNode;
use PhpParser\Node\Stmt\ClassMethod as MethodNode;
use PhpParser\Node\Stmt\Enum_ as EnumNode;
use PhpParser\Node\Stmt\Interface_ as InterfaceNode;
use PhpParser\Node\Stmt\Property as PropertyNode;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\Parser\Php7;
use PHPStan\PhpDocParser\Lexer\Lexer as PHPStanPhpDocLexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser as PHPStanPhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser as PHPStanTypeParser;

/**
 * @api
 */
final class TypeReflector
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
        $this->phpDocParser = new PHPDocParser($phpDocParser, $phpDocLexer, $tagPrioritizer);
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return ClassLikeReflection<T>
     */
    public function reflectClassLike(string $class): ClassLikeReflection
    {
        [$node, $nameContext] = $this->parseClass($class);
        $phpDoc = $this->phpDocParser->parse($node);
        $scope = $this->createClassLikeScope($class, $node, $nameContext, $phpDoc);

        $builder = new ClassLikeReflectionBuilder($class);
        $builder->templates($this->parseTemplates($scope, $phpDoc));
        $this->addInheritedClassLikes($builder, $scope, $node, $phpDoc);
        $this->addProperties($builder, $scope, $node->getProperties());
        $this->addMethods($builder, $scope, $node->getMethods());

        return $builder->build();
    }

    /**
     * @param class-string $class
     * @return array{ClassLikeNode, NameContext}
     */
    private function parseClass(string $class): array
    {
        $source = $this->classLocator->locateClass($class);

        if ($source === null) {
            throw new \RuntimeException(sprintf('Failed to locate class %s.', $class));
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
            throw new \RuntimeException();
        }

        return [$node, $nameResolver->getNameContext()];
    }

    /**
     * @param class-string $class
     */
    private function createClassLikeScope(string $class, ClassLikeNode $node, NameContext $nameContext, PHPDoc $phpDoc): ClassLikeScope
    {
        return new ClassLikeScope(
            nameContext: $nameContext,
            name: $class,
            parent: $node instanceof ClassNode && $node->extends !== null ? TypeParser::nameToClass($node->extends) : null,
            final: $node instanceof ClassNode && $node->isFinal(),
            templateNames: $phpDoc->templateNames(),
        );
    }

    /**
     * @return array<non-empty-string, TemplateReflection>
     */
    private function parseTemplates(Scope $scope, PHPDoc $phpDoc): array
    {
        $templates = [];
        $index = 0;

        foreach ($phpDoc->templates() as $tagName => $tagValue) {
            $templates[$tagValue->name] = new TemplateReflection(
                index: $index++,
                name: $tagValue->name,
                constraint: $this->typeParser->parsePHPDocType($scope, $tagValue->bound) ?? types::mixed,
                variance: match (true) {
                    str_ends_with($tagName, 'covariant') => Variance::COVARIANT,
                    str_ends_with($tagName, 'contravariant') => Variance::CONTRAVARIANT,
                    default => Variance::INVARIANT,
                },
            );
        }

        return $templates;
    }

    private function addInheritedClassLikes(ClassLikeReflectionBuilder $classBuilder, ClassLikeScope $classScope, ClassLikeNode $node, PHPDoc $phpDoc): void
    {
        $templateArguments = [];

        foreach ($phpDoc->inheritedTypes() as $phpDocInheritedType) {
            $type = $this->typeParser->parsePHPDocType($classScope, $phpDocInheritedType);
            \assert($type instanceof NamedObjectType);
            $templateArguments[$type->class] = $type->templateArguments;
        }

        foreach ($this->collectClassLikeInheritedNames($node) as $inheritedName) {
            $class = TypeParser::nameToClass($inheritedName);
            $classLike = $this->reflectClassLike($class)->resolveTemplates($templateArguments[$class] ?? []);
            $classBuilder->addInheritedClassLike($classLike);
        }
    }

    /**
     * @return \Generator<NameNode>
     */
    private function collectClassLikeInheritedNames(ClassLikeNode $node): \Generator
    {
        if ($node instanceof ClassNode) {
            if ($node->extends !== null) {
                yield $node->extends;
            }

            yield from $node->implements;

            return;
        }

        if ($node instanceof InterfaceNode) {
            yield from $node->extends;

            return;
        }

        if ($node instanceof EnumNode) {
            yield from $node->implements;
        }
    }

    /**
     * @param array<PropertyNode> $propertyNodes
     */
    private function addProperties(ClassLikeReflectionBuilder $classBuilder, ClassLikeScope $classScope, array $propertyNodes): void
    {
        $staticPropertyScope = null;
        $nonStaticPropertyScope = null;

        foreach ($propertyNodes as $propertyNode) {
            if ($propertyNode->isStatic()) {
                $propertyScope = $staticPropertyScope ??= new PropertyScope($classScope, static: true);
            } else {
                $propertyScope = $nonStaticPropertyScope ??= new PropertyScope($classScope, static: false);
            }

            $propertyPHPDoc = $this->phpDocParser->parse($propertyNode);
            $nativeType = $this->typeParser->parseNativeType($propertyScope, $propertyNode->type);
            $phpDocType = $this->typeParser->parsePHPDocType($propertyScope, $propertyPHPDoc->varType());

            foreach ($propertyNode->props as $eachProperty) {
                $classBuilder->property($eachProperty->name->name)
                    ->inheritable(!$propertyNode->isPrivate())
                    ->type
                    ->nativeType($nativeType)
                    ->phpDocType($phpDocType);
            }
        }
    }

    /**
     * @param array<MethodNode> $methodNodes
     */
    private function addMethods(ClassLikeReflectionBuilder $classBuilder, ClassLikeScope $classScope, array $methodNodes): void
    {
        foreach ($methodNodes as $methodNode) {
            $methodName = $methodNode->name->name;
            $isConstructor = $methodName === '__construct';
            $methodPHPDoc = $this->phpDocParser->parse($methodNode);
            $methodScope = new MethodScope(
                classScope: $classScope,
                name: $methodName,
                static: $methodNode->isStatic(),
                templateNames: $methodPHPDoc->templateNames(),
            );

            $methodBuilder = $classBuilder->method($methodName)
                ->inheritable(!$methodNode->isPrivate())
                ->templates($this->parseTemplates($methodScope, $methodPHPDoc));
            $methodBuilder->returnType
                ->nativeType($this->typeParser->parseNativeType($methodScope, $methodNode->returnType))
                ->phpDocType($this->typeParser->parsePHPDocType($methodScope, $methodPHPDoc->returnType()));

            foreach ($methodNode->params as $parameterNode) {
                \assert($parameterNode->var instanceof VariableNode && \is_string($parameterNode->var->name));

                $parameterName = $parameterNode->var->name;
                $nativeParameterType = $this->typeParser->parseNativeType($methodScope, $parameterNode->type);
                $phpDocParameterType = $this->typeParser->parsePHPDocType($methodScope, $methodPHPDoc->paramType($parameterName));

                $methodBuilder->parameterType($parameterName)
                    ->nativeType($nativeParameterType)
                    ->phpDocType($phpDocParameterType);

                if ($isConstructor && $this->isParameterNodePromoted($parameterNode)) {
                    $classBuilder->property($parameterName)
                        ->inheritable(!($parameterNode->flags & ClassNode::MODIFIER_PRIVATE))
                        ->type
                        ->nativeType($nativeParameterType)
                        ->phpDocType($phpDocParameterType);
                }
            }
        }
    }

    private function isParameterNodePromoted(ParameterNode $node): bool
    {
        return $node->flags & ClassNode::MODIFIER_PUBLIC
            || $node->flags & ClassNode::MODIFIER_PROTECTED
            || $node->flags & ClassNode::MODIFIER_PRIVATE;
    }
}
