<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpParserReflector;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use Typhoon\Reflection\FileResource;
use Typhoon\Reflection\Metadata\AttributeMetadata;
use Typhoon\Reflection\Metadata\ClassMetadata;
use Typhoon\Reflection\Metadata\MethodMetadata;
use Typhoon\Reflection\Metadata\ParameterMetadata;
use Typhoon\Reflection\Metadata\PropertyMetadata;
use Typhoon\Reflection\Metadata\TraitMethodAlias;
use Typhoon\Reflection\Metadata\TypeMetadata;
use Typhoon\Reflection\PhpDocParser\ContextualPhpDocTypeReflector;
use Typhoon\Reflection\PhpDocParser\PhpDoc;
use Typhoon\Reflection\PhpDocParser\PhpDocParser;
use Typhoon\Reflection\TemplateReflection;
use Typhoon\Reflection\TypeAlias\ImportedType;
use Typhoon\Reflection\TypeContext\TypeContext;
use Typhoon\Type;
use Typhoon\Type\types;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\PhpParserReflector
 * @psalm-import-type TraitMethodAliases from ClassMetadata
 * @psalm-import-type TraitMethodPrecedence from ClassMetadata
 */
final class ContextualPhpParserReflector
{
    private ContextualPhpDocTypeReflector $phpDocTypeReflector;

    /**
     * @var \WeakMap<Node, PhpDoc>
     */
    private \WeakMap $memoizedPhpDocs;

    public function __construct(
        private readonly PhpDocParser $phpDocParser,
        private TypeContext $typeContext,
        private readonly FileResource $file,
    ) {
        $this->phpDocTypeReflector = new ContextualPhpDocTypeReflector($typeContext);
        /** @var \WeakMap<Node, PhpDoc> */
        $this->memoizedPhpDocs = new \WeakMap();
    }

    /**
     * @return class-string
     */
    public function resolveClassName(Node\Identifier $name): string
    {
        return $this->resolveNameAsClass($name);
    }

    /**
     * @template TObject of object
     * @param class-string<TObject> $name
     * @return ClassMetadata<TObject>
     */
    public function reflectClass(Stmt\ClassLike $node, string $name): ClassMetadata
    {
        $phpDoc = $this->parsePhpDoc($node);
        [$traitTypes, $traitMethodAliases, $traitMethodPrecedence] = $this->reflectTraits($node);

        return $this->executeWithTypes(types::atClass($name), $phpDoc, fn(): ClassMetadata => new ClassMetadata(
            name: $name,
            modifiers: ClassReflections::modifiers($node),
            changeDetector: $this->file->changeDetector(),
            internal: $this->file->isInternal(),
            extension: $this->file->extension,
            file: $this->file->isInternal() ? false : $this->file->file,
            startLine: $this->reflectLine($node->getStartLine()),
            endLine: $this->reflectLine($node->getEndLine()),
            docComment: $this->reflectDocComment($node),
            attributes: $this->reflectAttributes($node->attrGroups, \Attribute::TARGET_CLASS),
            typeAliases: $this->reflectTypeAliasesFromContext($phpDoc),
            templates: $this->reflectTemplatesFromContext($phpDoc),
            interface: $node instanceof Stmt\Interface_,
            enum: $node instanceof Stmt\Enum_,
            trait: $node instanceof Stmt\Trait_,
            anonymous: $node->name === null,
            deprecated: $phpDoc->isDeprecated(),
            parentType: $this->reflectParentType($node, $phpDoc),
            interfaceTypes: $this->reflectInterfaceTypes($node, $phpDoc),
            traitTypes: $traitTypes,
            traitMethodAliases: $traitMethodAliases,
            traitMethodPrecedence: $traitMethodPrecedence,
            ownProperties: $this->reflectOwnProperties($name, $node),
            ownMethods: $this->reflectOwnMethods($name, $node),
        ));
    }

    public function __clone()
    {
        $this->typeContext = clone $this->typeContext;
        $this->phpDocTypeReflector = new ContextualPhpDocTypeReflector($this->typeContext);
    }

    /**
     * @param array<Node\AttributeGroup> $attrGroups
     * @param \Attribute::TARGET_* $target
     * @return list<AttributeMetadata>
     */
    private function reflectAttributes(array $attrGroups, int $target): array
    {
        /** @var list<class-string> */
        $names = [];
        /** @var array<class-string, bool> */
        $repeated = [];

        foreach ($attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                $name = $this->resolveNameAsClass($attr->name);

                if (str_starts_with($name, 'JetBrains\PhpStorm\Internal')) {
                    continue;
                }

                $names[] = $name;

                if (isset($repeated[$name])) {
                    $repeated[$name] = true;
                } else {
                    $repeated[$name] = false;
                }
            }
        }

        $attributes = [];

        foreach ($names as $position => $name) {
            $attributes[] = new AttributeMetadata(
                name: $name,
                position: $position,
                target: $target,
                repeated: $repeated[$name],
            );
        }

        return $attributes;
    }

    private function reflectParentType(Stmt\ClassLike $node, PhpDoc $phpDoc): ?Type\NamedObjectType
    {
        if (!$node instanceof Stmt\Class_ || $node->extends === null) {
            return null;
        }

        $parentClass = $this->resolveNameAsClass($node->extends);

        foreach ($phpDoc->extendedTypes() as $phpDocExtendedType) {
            /** @var Type\NamedObjectType $extendedType */
            $extendedType = $this->phpDocTypeReflector->reflect($phpDocExtendedType);

            if ($extendedType->class === $parentClass) {
                return $extendedType;
            }
        }

        return types::object($parentClass);
    }

    /**
     * @return list<Type\NamedObjectType>
     */
    private function reflectInterfaceTypes(Stmt\ClassLike $node, PhpDoc $phpDoc): array
    {
        if ($node instanceof Stmt\Interface_) {
            $names = $node->extends;
            $phpDocInterfaceTypes = $phpDoc->extendedTypes();
        } elseif ($node instanceof Stmt\Class_) {
            $names = $node->implements;
            $phpDocInterfaceTypes = $phpDoc->implementedTypes();
        } elseif ($node instanceof Stmt\Enum_) {
            $names = [
                ...$node->implements,
                new Name\FullyQualified(\UnitEnum::class),
                ...($node->scalarType === null ? [] : [new Name\FullyQualified(\BackedEnum::class)]),
            ];
            $phpDocInterfaceTypes = $phpDoc->implementedTypes();
        } else {
            return [];
        }

        if ($names === []) {
            return [];
        }

        $phpDocTypesByClass = [];

        foreach ($phpDocInterfaceTypes as $phpDocInterfaceType) {
            /** @var Type\NamedObjectType $implementedType */
            $implementedType = $this->phpDocTypeReflector->reflect($phpDocInterfaceType);
            $phpDocTypesByClass[$implementedType->class] = $implementedType;
        }

        $types = [];

        foreach ($names as $name) {
            $nameAsString = $name->toCodeString();

            // Fix for https://github.com/JetBrains/phpstorm-stubs/pull/1528.
            if (\in_array($nameAsString, ['iterable', 'callable'], true)) {
                continue;
            }

            $class = $this->resolveNameAsClass($nameAsString);
            $types[] = $phpDocTypesByClass[$class] ?? types::object($class);
        }

        return $types;
    }

    /**
     * @return array{list<Type\NamedObjectType>, TraitMethodAliases, TraitMethodPrecedence}
     */
    private function reflectTraits(Stmt\ClassLike $node): array
    {
        if ($node instanceof Stmt\Interface_) {
            return [[], [], []];
        }

        $phpDocTypesByClass = [];

        foreach ($node->getTraitUses() as $useNode) {
            foreach ($this->parsePhpDoc($useNode)->usedTypes() as $phpDocUsedType) {
                $usedType = $this->phpDocTypeReflector->reflect($phpDocUsedType);
                \assert($usedType instanceof Type\NamedObjectType);
                $phpDocTypesByClass[$usedType->class] = $usedType;
            }
        }

        $traitTypes = [];
        /** @var TraitMethodAliases */
        $traitMethodAliases = [];
        /** @var TraitMethodPrecedence */
        $traitMethodPrecedence = [];

        foreach ($node->getTraitUses() as $useNode) {
            $useTraitClasses = [];

            foreach ($useNode->traits as $name) {
                $useTraitClass = $this->resolveNameAsClass($name);
                $useTraitClasses[] = $useTraitClass;
                $traitTypes[] = $phpDocTypesByClass[$useTraitClass] ?? types::object($useTraitClass);
            }

            foreach ($useNode->adaptations as $adaptation) {
                if ($adaptation instanceof Stmt\TraitUseAdaptation\Alias) {
                    if ($adaptation->trait === null) {
                        $aliasClasses = $useTraitClasses;
                    } else {
                        $aliasClasses = [$this->resolveNameAsClass($adaptation->trait)];
                    }

                    foreach ($aliasClasses as $aliasClass) {
                        $traitMethodAliases[$aliasClass][$adaptation->method->name][] = new TraitMethodAlias(
                            visibility: $adaptation->newModifier,
                            alias: $adaptation->newName?->name,
                        );
                    }

                    continue;
                }

                if ($adaptation instanceof Stmt\TraitUseAdaptation\Precedence) {
                    \assert($adaptation->trait !== null);
                    $traitMethodPrecedence[$adaptation->method->name] = $this->resolveNameAsClass($adaptation->trait);
                }
            }
        }

        return [$traitTypes, $traitMethodAliases, $traitMethodPrecedence];
    }

    /**
     * @param class-string $class
     * @return list<PropertyMetadata>
     */
    private function reflectOwnProperties(string $class, Stmt\ClassLike $classNode): array
    {
        $classReadOnly = $classNode instanceof Stmt\Class_ && $classNode->isReadonly();
        $properties = [];

        if ($classNode instanceof Stmt\Enum_) {
            $properties[] = EnumReflections::name($class);

            if ($classNode->scalarType !== null) {
                $properties[] = EnumReflections::value($class, $this->reflectType($classNode->scalarType));
            }
        }

        foreach ($classNode->getProperties() as $node) {
            $phpDoc = $this->parsePhpDoc($node);
            $type = $this->reflectType($node->type, $phpDoc->varType());

            foreach ($node->props as $property) {
                $properties[] = new PropertyMetadata(
                    name: $property->name->name,
                    class: $class,
                    modifiers: PropertyReflections::modifiers($node, $classReadOnly),
                    type: $type,
                    docComment: $this->reflectDocComment($node),
                    hasDefaultValue: $property->default !== null || $node->type === null,
                    deprecated: $phpDoc->isDeprecated(),
                    startLine: $this->reflectLine($node->getStartLine()),
                    endLine: $this->reflectLine($node->getEndLine()),
                    attributes: $this->reflectAttributes($node->attrGroups, \Attribute::TARGET_PROPERTY),
                );
            }
        }

        $constructorNode = $classNode->getMethod('__construct');

        if ($constructorNode === null) {
            return $properties;
        }

        $phpDoc = $this->parsePhpDoc($constructorNode);

        foreach ($constructorNode->params as $node) {
            $modifiers = PropertyReflections::promotedModifiers($node, $classReadOnly);

            if ($modifiers === 0) {
                continue;
            }

            \assert($node->var instanceof Expr\Variable && \is_string($node->var->name));
            $name = $node->var->name;
            $properties[] = new PropertyMetadata(
                name: $name,
                class: $class,
                modifiers: $modifiers,
                type: $this->reflectType($node->type, $phpDoc->paramTypes()[$name] ?? null),
                docComment: $this->reflectDocComment($node),
                hasDefaultValue: $node->default !== null || $node->type === null,
                promoted: true,
                deprecated: $phpDoc->isDeprecated(),
                startLine: $this->reflectLine($node->getStartLine()),
                endLine: $this->reflectLine($node->getEndLine()),
                attributes: $this->reflectAttributes($node->attrGroups, \Attribute::TARGET_PROPERTY),
            );
        }

        return $properties;
    }

    /**
     * @param class-string $class
     * @return list<MethodMetadata>
     */
    private function reflectOwnMethods(string $class, Stmt\ClassLike $classNode): array
    {
        $interface = $classNode instanceof Stmt\Interface_;
        $methods = [];

        foreach ($classNode->getMethods() as $node) {
            $name = $node->name->name;
            $phpDoc = $this->parsePhpDoc($node);
            $declaredAt = types::atMethod($class, $name);
            $methods[] = $this->executeWithTypes($declaredAt, $phpDoc, fn(): MethodMetadata => new MethodMetadata(
                name: $name,
                class: $class,
                modifiers: MethodReflections::modifiers($node, $interface),
                parameters: $this->reflectParameters($node->params, $phpDoc, $class, $name),
                returnType: $this->reflectType($node->returnType, $phpDoc->returnType()),
                templates: $this->reflectTemplatesFromContext($phpDoc),
                docComment: $this->reflectDocComment($node),
                internal: $this->file->isInternal(),
                extension: $this->file->extension,
                file: $this->file->isInternal() ? false : $this->file->file,
                startLine: $this->reflectLine($node->getStartLine()),
                endLine: $this->reflectLine($node->getEndLine()),
                returnsReference: $node->byRef,
                generator: MethodReflections::isGenerator($node),
                deprecated: $phpDoc->isDeprecated(),
                attributes: $this->reflectAttributes($node->attrGroups, \Attribute::TARGET_METHOD),
            ));
        }

        if ($classNode instanceof Stmt\Enum_) {
            $methods[] = EnumReflections::cases($class);

            if ($classNode->scalarType !== null) {
                $valueType = $this->reflectType($classNode->scalarType);
                $methods[] = EnumReflections::from($class, $valueType);
                $methods[] = EnumReflections::tryFrom($class, $valueType);
            }
        }

        return $methods;
    }

    /**
     * @param array<Node\Param> $nodes
     * @param class-string $class
     * @param non-empty-string $functionOrMethod
     * @return list<ParameterMetadata>
     */
    private function reflectParameters(array $nodes, PhpDoc $methodPhpDoc, string $class, string $functionOrMethod): array
    {
        $parameters = [];
        $isOptional = false;

        foreach (array_values($nodes) as $position => $node) {
            \assert($node->var instanceof Expr\Variable && \is_string($node->var->name));
            $name = $node->var->name;
            $phpDoc = $this->parsePhpDoc($node);
            $isOptional = $isOptional || $node->default !== null || $node->variadic;
            $parameters[] = new ParameterMetadata(
                position: $position,
                name: $name,
                class: $class,
                functionOrMethod: $functionOrMethod,
                type: $this->reflectType($node->type, $methodPhpDoc->paramTypes()[$name] ?? null, ParameterReflections::isDefaultNull($node)),
                passedByReference: $node->byRef,
                defaultValueAvailable: $node->default !== null,
                optional: $isOptional,
                variadic: $node->variadic,
                promoted: ParameterReflections::isPromoted($node),
                deprecated: $phpDoc->isDeprecated(),
                startLine: $this->reflectLine($node->getStartLine()),
                endLine: $this->reflectLine($node->getEndLine()),
                attributes: $this->reflectAttributes($node->attrGroups, \Attribute::TARGET_PARAMETER),
            );
        }

        return $parameters;
    }

    private function reflectType(?Node $native = null, ?TypeNode $phpDoc = null, bool $implicitlyNullable = false): TypeMetadata
    {
        return TypeMetadata::create(
            native: $native === null ? null : NativeTypeReflections::reflect($this->typeContext, $native, $implicitlyNullable),
            phpDoc: $phpDoc === null ? null : $this->phpDocTypeReflector->reflect($phpDoc),
        );
    }

    /**
     * @return list<TemplateReflection>
     */
    private function reflectTemplatesFromContext(PhpDoc $phpDoc): array
    {
        $reflections = [];

        foreach ($phpDoc->templates() as $position => $node) {
            $templateType = $this->typeContext->resolveNameAsType($node->name);
            \assert($templateType instanceof Type\TemplateType);
            $reflections[] = new TemplateReflection(
                name: $node->name,
                position: $position,
                constraint: $templateType->constraint,
                variance: PhpDoc::templateTagVariance($node),
            );
        }

        return $reflections;
    }

    /**
     * @return array<non-empty-string, Type\Type>
     */
    private function reflectTypeAliasesFromContext(PhpDoc $phpDoc): array
    {
        $typeAliases = [];

        foreach ($phpDoc->typeAliases() as $typeAlias) {
            $typeAliases[$typeAlias->alias] = $this->typeContext->resolveNameAsType($typeAlias->alias);
        }

        foreach ($phpDoc->typeAliasImports() as $typeImport) {
            $alias = $typeImport->importedAs ?? $typeImport->importedAlias;
            $typeAliases[$alias] = $this->typeContext->resolveNameAsType($alias);
        }

        return $typeAliases;
    }

    /**
     * @template TReturn
     * @param \Closure(): TReturn $action
     * @return TReturn
     */
    private function executeWithTypes(Type\AtClass|Type\AtMethod $declaredAt, PhpDoc $phpDoc, \Closure $action): mixed
    {
        $class = match (true) {
            $declaredAt instanceof Type\AtClass => $declaredAt->name,
            $declaredAt instanceof Type\AtMethod => $declaredAt->class,
            default => null,
        };
        $types = [];

        foreach ($phpDoc->typeAliases() as $typeAlias) {
            $types[$typeAlias->alias] = fn(): Type\Type => $this->phpDocTypeReflector->reflect($typeAlias->type);
        }

        foreach ($phpDoc->typeAliasImports() as $typeImport) {
            $alias = $typeImport->importedAs ?? $typeImport->importedAlias;
            $types[$alias] = function () use ($class, $typeImport): Type\Type {
                $fromClass = $this->resolveNameAsClass($typeImport->importedFrom);

                if ($fromClass === $class) {
                    return $this->typeContext->resolveNameAsType($typeImport->importedAlias);
                }

                return new ImportedType($fromClass, $typeImport->importedAlias);
            };
        }

        foreach ($phpDoc->templates() as $template) {
            $types[$template->name] = fn(): Type\TemplateType => types::template(
                name: $template->name,
                declaredAt: $declaredAt,
                constraint: $template->bound === null ? types::mixed : $this->phpDocTypeReflector->reflect($template->bound),
            );
        }

        return $this->typeContext->executeWithTypes($action, $types);
    }

    /**
     * @return class-string
     */
    private function resolveNameAsClass(string|Node\Identifier|Name|IdentifierTypeNode $name): string
    {
        return $this->typeContext->resolveNameAsClass(match (true) {
            $name instanceof Name => $name->toCodeString(),
            default => (string) $name,
        });
    }

    private function parsePhpDoc(Node $node): PhpDoc
    {
        if (isset($this->memoizedPhpDocs[$node])) {
            return $this->memoizedPhpDocs[$node];
        }

        $text = $node->getDocComment()?->getText();

        if ($text === null || $text === '') {
            return PhpDoc::empty();
        }

        return $this->memoizedPhpDocs[$node] = $this->phpDocParser->parsePhpDoc($text);
    }

    /**
     * @return non-empty-string|false
     */
    private function reflectDocComment(Node $node): string|false
    {
        if ($this->file->isInternal()) {
            return false;
        }

        $text = $node->getDocComment()?->getText() ?? '';

        return $text ?: false;
    }

    /**
     * @return positive-int|false
     */
    private function reflectLine(int $line): int|false
    {
        if ($this->file->isInternal()) {
            return false;
        }

        return $line > 0 ? $line : false;
    }
}
