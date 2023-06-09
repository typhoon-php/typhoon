<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\TypeReflector;

use ExtendedTypeSystem\Reflection\PHPDocParser\PHPDoc;
use ExtendedTypeSystem\Reflection\PHPDocParser\PHPDocParser;
use ExtendedTypeSystem\Reflection\Scope;
use ExtendedTypeSystem\Reflection\Scope\ClassLikeScope;
use ExtendedTypeSystem\Reflection\Scope\MethodScope;
use ExtendedTypeSystem\Reflection\Scope\NameContextScope;
use ExtendedTypeSystem\Reflection\Scope\PropertyScope;
use ExtendedTypeSystem\Reflection\TemplateReflection;
use ExtendedTypeSystem\Reflection\TypeReflectionException;
use ExtendedTypeSystem\Reflection\Variance;
use ExtendedTypeSystem\Type\NamedObjectType;
use ExtendedTypeSystem\types;
use PhpParser\ErrorHandler\Throwing;
use PhpParser\NameContext;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem\Reflection
 * @template T of object
 */
final class ClassLikeReflectionVisitor extends NodeVisitorAbstract
{
    /**
     * @psalm-readonly-allow-private-mutation
     * @var ?ClassLikeMetadata<T>
     */
    public ?ClassLikeMetadata $metadata = null;

    private NameContext $nameContext;

    private ?ClassLikeScope $classScope = null;

    /**
     * @var ClassLikeReflectionBuilder<T>
     */
    private ClassLikeReflectionBuilder $builder;

    /**
     * @param \Closure(class-string): ClassLikeMetadata $reflector
     * @param class-string<T> $class
     */
    public function __construct(
        private readonly string $class,
        private readonly \Closure $reflector,
        private readonly PHPDocParser $phpDocParser,
    ) {
        $this->nameContext = new NameContext(new Throwing());
        $this->builder = new ClassLikeReflectionBuilder($class);
    }

    public function beforeTraverse(array $nodes): ?array
    {
        $this->checkNotClosed();

        return null;
    }

    public function enterNode(Node $node): ?int
    {
        $this->checkNotClosed();

        if ($node instanceof Stmt\Namespace_) {
            $this->nameContext->startNamespace($node->name);

            return null;
        }

        if ($node instanceof Stmt\Use_) {
            $this->enterUse($node);

            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        if ($node instanceof Stmt\GroupUse) {
            $this->enterGroupUse($node);

            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        if ($node instanceof Stmt\ClassLike) {
            if ($node->name === null) {
                return NodeTraverser::DONT_TRAVERSE_CHILDREN;
            }

            $name = $this->nameContext->getResolvedClassName(new Name($node->name->name));

            if ($name->toString() !== $this->class) {
                return NodeTraverser::DONT_TRAVERSE_CHILDREN;
            }

            $this->enterClassLike($node);

            return null;
        }

        if ($node instanceof Stmt\Property) {
            $this->enterProperty($node);

            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        if ($node instanceof Stmt\ClassMethod) {
            $this->enterMethod($node);

            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        return NodeTraverser::DONT_TRAVERSE_CHILDREN;
    }

    public function leaveNode(Node $node): ?int
    {
        if ($node instanceof Stmt\ClassLike && $this->classScope !== null) {
            $this->classScope = null;
            $this->metadata = $this->builder->build();

            return NodeTraverser::STOP_TRAVERSAL;
        }

        $this->checkNotClosed();

        return null;
    }

    private function enterUse(Stmt\Use_ $node): void
    {
        foreach ($node->uses as $use) {
            $this->nameContext->addAlias(
                name: $use->name,
                aliasName: (string) $use->getAlias(),
                type: $node->type | $use->type,
                errorAttrs: $use->getAttributes(),
            );
        }
    }

    private function enterGroupUse(Stmt\GroupUse $node): void
    {
        foreach ($node->uses as $use) {
            $this->nameContext->addAlias(
                name: Name::concat($node->prefix, $use->name),
                aliasName: (string) $use->getAlias(),
                type: $node->type | $use->type,
                errorAttrs: $use->getAttributes(),
            );
        }
    }

    private function enterClassLike(Stmt\ClassLike $node): void
    {
        $phpDoc = $this->phpDocParser->parseNode($node);
        $nameContextScope = new NameContextScope($this->nameContext);
        $parent = null;

        if ($node instanceof Stmt\Class_ && $node->extends !== null) {
            $parent = $nameContextScope->resolveClass($node->extends);
        }

        $this->classScope = new ClassLikeScope(
            name: $this->class,
            parent: $parent,
            templateNames: array_keys($phpDoc->templates),
            parentScope: $nameContextScope,
        );
        $this->builder->templates($this->parseTemplates($this->classScope, $phpDoc));

        $templateArguments = [];

        foreach ($phpDoc->inheritedTypes as $phpDocInheritedType) {
            $type = TypeParser::parsePHPDocType($this->classScope, $phpDocInheritedType);
            \assert($type instanceof NamedObjectType);
            $templateArguments[$type->class] = $type->templateArguments;
        }

        foreach ($this->collectClassLikeInheritedNames($node) as $inheritedName) {
            $inheritedClass = $this->classScope->resolveClass($inheritedName);
            $classLike = ($this->reflector)($inheritedClass)->withResolvedTemplates($templateArguments[$inheritedClass] ?? []);
            $this->builder->addInheritedClassLike($classLike);
        }
    }

    private function enterProperty(Stmt\Property $node): void
    {
        \assert($this->classScope !== null);

        $propertyScope = new PropertyScope($this->classScope, $node->isStatic());
        $propertyPHPDoc = $this->phpDocParser->parseNode($node);
        $nativeType = TypeParser::parseNativeType($propertyScope, $node->type);
        $phpDocType = TypeParser::parsePHPDocType($propertyScope, $propertyPHPDoc->varType);

        foreach ($node->props as $eachProperty) {
            $name = $eachProperty->name->name;

            $this
                ->builder
                ->property($name)
                ->inheritable(!$node->isPrivate())
                ->type
                ->nativeType($nativeType)
                ->phpDocType($phpDocType);
        }
    }

    private function enterMethod(Stmt\ClassMethod $node): void
    {
        \assert($this->classScope !== null);

        $name = $node->name->name;
        $isConstructor = $name === '__construct';
        $phpDoc = $this->phpDocParser->parseNode($node);
        $methodScope = new MethodScope($this->classScope, $name, $node->isStatic(), array_keys($phpDoc->templates));

        $methodBuilder = $this
            ->builder
            ->method($name)
            ->inheritable(!$node->isPrivate())
            ->templates($this->parseTemplates($methodScope, $phpDoc));
        $methodBuilder->returnType
            ->nativeType(TypeParser::parseNativeType($methodScope, $node->returnType))
            ->phpDocType(TypeParser::parsePHPDocType($methodScope, $phpDoc->returnType));

        foreach ($node->params as $parameterNode) {
            \assert($parameterNode->var instanceof Expr\Variable && \is_string($parameterNode->var->name));

            $parameterName = $parameterNode->var->name;
            $nativeParameterType = TypeParser::parseNativeType($methodScope, $parameterNode->type);
            $phpDocParameterType = TypeParser::parsePHPDocType($methodScope, $phpDoc->paramTypes[$parameterName] ?? null);

            $methodBuilder->parameterType($parameterName)
                ->nativeType($nativeParameterType)
                ->phpDocType($phpDocParameterType);

            if ($isConstructor && $this->isParameterNodePromoted($parameterNode)) {
                $this
                    ->builder
                    ->property($parameterName)
                    ->inheritable(!($parameterNode->flags & Stmt\Class_::MODIFIER_PRIVATE))
                    ->type
                    ->nativeType($nativeParameterType)
                    ->phpDocType($phpDocParameterType);
            }
        }
    }

    /**
     * @return \Generator<Name>
     */
    private function collectClassLikeInheritedNames(Stmt\ClassLike $node): \Generator
    {
        if ($node instanceof Stmt\Class_) {
            if ($node->extends !== null) {
                yield $node->extends;
            }

            yield from $node->implements;

            return;
        }

        if ($node instanceof Stmt\Interface_) {
            yield from $node->extends;

            return;
        }

        if ($node instanceof Stmt\Enum_) {
            yield from $node->implements;
        }
    }

    /**
     * @return array<non-empty-string, TemplateReflection>
     */
    private function parseTemplates(Scope $scope, PHPDoc $phpDoc): array
    {
        $templates = [];

        foreach (array_values($phpDoc->templates) as $position => $template) {
            $variance = $template->getAttribute('variance');
            $templates[$template->name] = new TemplateReflection(
                position: $position,
                name: $template->name,
                constraint: TypeParser::parsePHPDocType($scope, $template->bound) ?? types::mixed,
                variance: $variance instanceof Variance ? $variance : Variance::INVARIANT,
            );
        }

        return $templates;
    }

    private function isParameterNodePromoted(Param $node): bool
    {
        return $node->flags & Stmt\Class_::MODIFIER_PUBLIC
            || $node->flags & Stmt\Class_::MODIFIER_PROTECTED
            || $node->flags & Stmt\Class_::MODIFIER_PRIVATE;
    }

    private function checkNotClosed(): void
    {
        if ($this->metadata !== null) {
            throw new TypeReflectionException(sprintf('%s must not be used more than once.', self::class));
        }
    }
}
