<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpParserReflector;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use Typhoon\Reflection\FileResource;
use Typhoon\Reflection\Metadata\AttributeMetadata;
use Typhoon\Reflection\Metadata\ChangeDetector;
use Typhoon\Reflection\Metadata\ClassConstantMetadata;
use Typhoon\Reflection\Metadata\ClassMetadata;
use Typhoon\Reflection\Metadata\InheritedName;
use Typhoon\Reflection\Metadata\MethodMetadata;
use Typhoon\Reflection\Metadata\ParameterMetadata;
use Typhoon\Reflection\Metadata\PropertyMetadata;
use Typhoon\Reflection\Metadata\TraitMethodAlias;
use Typhoon\Reflection\Metadata\TypeMetadata;
use Typhoon\Reflection\PhpDocParser\ContextualPhpDocTypeReflector;
use Typhoon\Reflection\PhpDocParser\PhpDoc;
use Typhoon\Reflection\PhpDocParser\PhpDocParser;
use Typhoon\Reflection\TemplateReflection;
use Typhoon\Reflection\TypeContext\TypeContext;
use Typhoon\Type\AtClass;
use Typhoon\Type\AtMethod;
use Typhoon\Type\Type;
use Typhoon\Type\types;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\PhpParserReflector
 * @psalm-import-type TraitMethodAliases from ClassMetadata
 * @psalm-import-type TraitMethodPrecedence from ClassMetadata
 * @psalm-import-type Types from TypeContext as ContextTypes
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
     * @template TObject of object
     * @param class-string<TObject> $name
     * @return ClassMetadata<TObject>
     */
    public function reflectClass(Stmt\ClassLike $node, string $name): ClassMetadata
    {
        $phpDoc = $this->parsePhpDoc($node);
        $trait = $node instanceof Stmt\Trait_;
        $this->typeContext->enterClass(
            name: $name,
            trait: $trait,
            aliasTypes: $this->reflectClassAliasTypes($phpDoc),
            templateTypes: $this->reflectTemplateTypes(types::atClass($name), $phpDoc),
        );

        try {
            [$traitTypes, $traitMethodAliases, $traitMethodPrecedence] = $this->reflectTraits($node);
            $enumType = $node instanceof Stmt\Enum_ && $node->scalarType !== null
                ? NativeTypeReflections::reflect($this->typeContext, $node->scalarType)
                : null;

            return new ClassMetadata(
                name: $name,
                modifiers: ClassReflections::modifiers($node),
                changeDetector: ChangeDetector::fromFileContents($this->file->file, $this->file->contents()),
                internal: $this->file->isInternal(),
                extension: $this->file->extension,
                file: $this->file->isInternal() ? false : $this->file->file,
                startLine: $this->reflectLine($node->getStartLine()),
                endLine: $this->reflectLine($node->getEndLine()),
                docComment: $this->reflectDocComment($node),
                attributes: $this->reflectAttributes($node->attrGroups, \Attribute::TARGET_CLASS),
                typeAliases: $this->reflectTypeAliases($phpDoc),
                templates: $this->reflectTemplatesFromContext($phpDoc),
                interface: $node instanceof Stmt\Interface_,
                enum: $node instanceof Stmt\Enum_,
                trait: $trait,
                anonymous: $node->name === null,
                deprecated: $phpDoc->hasDeprecated(),
                parentType: $this->reflectParentType($node, $phpDoc),
                interfaceTypes: $this->reflectInterfaceTypes($node, $phpDoc),
                traitTypes: $traitTypes,
                traitMethodAliases: $traitMethodAliases,
                traitMethodPrecedence: $traitMethodPrecedence,
                ownConstants: $this->reflectOwnConstants($name, $node),
                ownProperties: $this->reflectOwnProperties($name, $node),
                ownMethods: $this->reflectOwnMethods($name, $node, $enumType),
                finalPhpDoc: $phpDoc->hasFinal(),
                readonlyPhpDoc: $phpDoc->hasReadonly(),
            );
        } finally {
            $this->typeContext->leaveClass();
        }
    }

    public function __clone()
    {
        $this->typeContext = clone $this->typeContext;
        $this->phpDocTypeReflector = new ContextualPhpDocTypeReflector($this->typeContext);
    }

    /**
     * @return non-empty-string
     */
    public function resolveNameAsClass(string|Node\Identifier|Name|IdentifierTypeNode $name): string
    {
        return $this->typeContext->resolveNameAsClass(match (true) {
            $name instanceof Name => $name->toCodeString(),
            default => (string) $name,
        });
    }

    /**
     * @param class-string $class
     * @return list<ClassConstantMetadata>
     */
    private function reflectOwnConstants(string $class, Stmt\ClassLike $classNode): array
    {
        $constants = [];

        foreach ($classNode->stmts as $node) {
            if ($node instanceof Stmt\ClassConst) {
                $phpDoc = $this->parsePhpDoc($node);
                $type = $this->reflectType($node->type, $phpDoc->varType());

                foreach ($node->consts as $const) {
                    $constants[] = new ClassConstantMetadata(
                        name: $const->name->name,
                        class: $class,
                        modifiers: ClassConstantReflections::modifiers($node),
                        type: $type,
                        docComment: $this->reflectDocComment($node),
                        startLine: $this->reflectLine($node->getStartLine()),
                        endLine: $this->reflectLine($node->getEndLine()),
                        attributes: $this->reflectAttributes($node->attrGroups, \Attribute::TARGET_CLASS_CONSTANT),
                    );
                }
            } elseif ($node instanceof Stmt\EnumCase) {
                $constants[] = new ClassConstantMetadata(
                    name: $node->name->name,
                    class: $class,
                    modifiers: \ReflectionClassConstant::IS_PUBLIC,
                    type: TypeMetadata::create(),
                    docComment: $this->reflectDocComment($node),
                    startLine: $this->reflectLine($node->getStartLine()),
                    endLine: $this->reflectLine($node->getEndLine()),
                    enumCase: true,
                    attributes: $this->reflectAttributes($node->attrGroups, \Attribute::TARGET_CLASS_CONSTANT),
                );
            }
        }

        return $constants;
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

    private function reflectParentType(Stmt\ClassLike $node, PhpDoc $phpDoc): ?InheritedName
    {
        if (!$node instanceof Stmt\Class_ || $node->extends === null) {
            return null;
        }

        $parentClass = $this->resolveNameAsClass($node->extends);

        foreach ($phpDoc->extendedTypes() as $phpDocExtendedType) {
            if ($this->resolveNameAsClass($phpDocExtendedType->type) === $parentClass) {
                return new InheritedName($parentClass, array_map($this->phpDocTypeReflector->reflect(...), $phpDocExtendedType->genericTypes));
            }
        }

        return new InheritedName($parentClass);
    }

    /**
     * @return list<InheritedName>
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

        $templateArgumentsByClass = [];

        foreach ($phpDocInterfaceTypes as $phpDocInterfaceType) {
            $templateArgumentsByClass[$this->resolveNameAsClass($phpDocInterfaceType->type)] = array_map(
                $this->phpDocTypeReflector->reflect(...),
                $phpDocInterfaceType->genericTypes,
            );
        }

        $inheritedNames = [];

        foreach ($names as $name) {
            $nameAsString = $name->toCodeString();

            // Fix for https://github.com/JetBrains/phpstorm-stubs/pull/1528.
            if (\in_array($nameAsString, ['iterable', 'callable'], true)) {
                continue;
            }

            $class = $this->resolveNameAsClass($nameAsString);
            $inheritedNames[] = new InheritedName($class, $templateArgumentsByClass[$class] ?? []);
        }

        return $inheritedNames;
    }

    /**
     * @return array{list<InheritedName>, TraitMethodAliases, TraitMethodPrecedence}
     */
    private function reflectTraits(Stmt\ClassLike $node): array
    {
        if ($node instanceof Stmt\Interface_) {
            return [[], [], []];
        }

        $templateArgumentsByClass = [];

        foreach ($node->getTraitUses() as $useNode) {
            foreach ($this->parsePhpDoc($useNode)->usedTypes() as $phpDocUsedType) {
                $templateArgumentsByClass[$this->resolveNameAsClass($phpDocUsedType->type)] = array_map(
                    $this->phpDocTypeReflector->reflect(...),
                    $phpDocUsedType->genericTypes,
                );
            }
        }

        $inheritedNames = [];
        /** @var TraitMethodAliases */
        $traitMethodAliases = [];
        /** @var TraitMethodPrecedence */
        $traitMethodPrecedence = [];

        foreach ($node->getTraitUses() as $useNode) {
            $useTraitClasses = [];

            foreach ($useNode->traits as $name) {
                $useTraitClass = $this->resolveNameAsClass($name);
                $useTraitClasses[] = $useTraitClass;
                $inheritedNames[] = new InheritedName($useTraitClass, $templateArgumentsByClass[$useTraitClass] ?? []);
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

        return [$inheritedNames, $traitMethodAliases, $traitMethodPrecedence];
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
                    deprecated: $phpDoc->hasDeprecated(),
                    startLine: $this->reflectLine($node->getStartLine()),
                    endLine: $this->reflectLine($node->getEndLine()),
                    attributes: $this->reflectAttributes($node->attrGroups, \Attribute::TARGET_PROPERTY),
                    readonlyPhpDoc: $phpDoc->hasReadonly(),
                );
            }
        }

        $constructorNode = $classNode->getMethod('__construct');

        if ($constructorNode === null) {
            return $properties;
        }

        $constructorPhpDoc = $this->parsePhpDoc($constructorNode);

        foreach ($constructorNode->params as $node) {
            $modifiers = PropertyReflections::promotedModifiers($node, $classReadOnly);

            if ($modifiers === 0) {
                continue;
            }

            \assert($node->var instanceof Expr\Variable && \is_string($node->var->name));
            $name = $node->var->name;
            $phpDoc = $this->parsePhpDoc($node);
            $properties[] = new PropertyMetadata(
                name: $name,
                class: $class,
                modifiers: $modifiers,
                type: $this->reflectType($node->type, $phpDoc->varType() ?? $constructorPhpDoc->paramTypes()[$name] ?? null),
                docComment: $this->reflectDocComment($node),
                hasDefaultValue: $node->default !== null || $node->type === null,
                promoted: true,
                deprecated: $phpDoc->hasDeprecated(),
                startLine: $this->reflectLine($node->getStartLine()),
                endLine: $this->reflectLine($node->getEndLine()),
                attributes: $this->reflectAttributes($node->attrGroups, \Attribute::TARGET_PROPERTY),
                readonlyPhpDoc: $phpDoc->hasReadonly(),
            );
        }

        return $properties;
    }

    /**
     * @param class-string $class
     * @return list<MethodMetadata>
     */
    private function reflectOwnMethods(string $class, Stmt\ClassLike $classNode, ?Type $enumType): array
    {
        $interface = $classNode instanceof Stmt\Interface_;
        $methods = [];

        foreach ($classNode->getMethods() as $node) {
            $name = $node->name->name;
            $phpDoc = $this->parsePhpDoc($node);
            $this->typeContext->enterMethod($this->reflectTemplateTypes(types::atMethod($class, $name), $phpDoc));

            try {
                $returnType = $this->reflectType($node->returnType, $phpDoc->returnType());
                $isTentative = $this->isTentativeType($node->attrGroups);

                $methods[] = new MethodMetadata(
                    name: $name,
                    class: $class,
                    modifiers: MethodReflections::modifiers($node, $interface),
                    parameters: $this->reflectParameters($node->params, $phpDoc, $class, $name),
                    returnType: $isTentative ? TypeMetadata::create(phpDoc: $returnType->phpDoc) : $returnType,
                    tentativeReturnType: $isTentative ? $returnType->native : null,
                    templates: $this->reflectTemplatesFromContext($phpDoc),
                    docComment: $this->reflectDocComment($node),
                    internal: $this->file->isInternal(),
                    extension: $this->file->extension,
                    file: $this->file->isInternal() ? false : $this->file->file,
                    startLine: $this->reflectLine($node->getStartLine()),
                    endLine: $this->reflectLine($node->getEndLine()),
                    returnsReference: $node->byRef,
                    generator: MethodReflections::isGenerator($node),
                    deprecated: $phpDoc->hasDeprecated(),
                    throwsTypePhpDoc: $this->reflectThrowsType($phpDoc->throwsTypes()),
                    attributes: $this->reflectAttributes($node->attrGroups, \Attribute::TARGET_METHOD),
                    finalPhpDoc: $phpDoc->hasFinal(),
                );
            } finally {
                $this->typeContext->leaveMethod();
            }
        }

        if ($classNode instanceof Stmt\Enum_) {
            $methods[] = EnumReflections::cases($class);

            if ($enumType !== null) {
                $methods[] = EnumReflections::from($class, $enumType);
                $methods[] = EnumReflections::tryFrom($class, $enumType);
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
                deprecated: $phpDoc->hasDeprecated(),
                startLine: $this->reflectLine($node->getStartLine()),
                endLine: $this->reflectLine($node->getEndLine()),
                attributes: $this->reflectAttributes($node->attrGroups, \Attribute::TARGET_PARAMETER),
            );
        }

        return $parameters;
    }

    private function reflectType(null|Node\Identifier|Name|Node\ComplexType $native = null, ?TypeNode $phpDoc = null, bool $implicitlyNullable = false): TypeMetadata
    {
        return TypeMetadata::create(
            native: $native === null ? null : NativeTypeReflections::reflect($this->typeContext, $native, $implicitlyNullable),
            phpDoc: $phpDoc === null ? null : $this->phpDocTypeReflector->reflect($phpDoc),
        );
    }

    /**
     * @param list<TypeNode> $throwsTypes
     */
    private function reflectThrowsType(array $throwsTypes): ?Type
    {
        if ($throwsTypes === []) {
            return null;
        }

        if (\count($throwsTypes) === 1) {
            return $this->phpDocTypeReflector->reflect($throwsTypes[0]);
        }

        $flatTypes = [];

        foreach ($throwsTypes as $throwsType) {
            if ($throwsType instanceof UnionTypeNode) {
                foreach ($throwsType->types as $type) {
                    $flatTypes[] = $type;
                }
            } else {
                $flatTypes[] = $throwsType;
            }
        }

        return $this->phpDocTypeReflector->reflect(new UnionTypeNode($flatTypes));
    }

    /**
     * @return list<TemplateReflection>
     */
    private function reflectTemplatesFromContext(PhpDoc $phpDoc): array
    {
        $reflections = [];

        foreach ($phpDoc->templates() as $position => $node) {
            $reflections[] = new TemplateReflection(
                name: $node->name,
                position: $position,
                constraint: $node->bound === null ? types::mixed : $this->phpDocTypeReflector->reflect($node->bound),
                variance: PhpDoc::templateTagVariance($node),
            );
        }

        return $reflections;
    }

    /**
     * @return array<non-empty-string, Type>
     */
    private function reflectTypeAliases(PhpDoc $phpDoc): array
    {
        $typeAliases = [];

        foreach ($phpDoc->typeAliases() as $typeAlias) {
            $typeAliases[$typeAlias->alias] = $this->phpDocTypeReflector->reflect($typeAlias->type);
        }

        foreach ($phpDoc->typeAliasImports() as $typeImport) {
            $alias = $typeImport->importedAs ?? $typeImport->importedAlias;
            $typeAliases[$alias] = types::alias($this->resolveNameAsClass($typeImport->importedFrom), $typeImport->importedAlias);
        }

        return $typeAliases;
    }

    /**
     * @param array<Node\AttributeGroup> $attrGroups
     */
    private function isTentativeType(array $attrGroups): bool
    {
        foreach ($attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attr) {
                if ($attr->name->getLast() === 'TentativeType') {
                    return $this->resolveNameAsClass($attr->name) === 'JetBrains\PhpStorm\Internal\TentativeType';
                }
            }
        }

        return false;
    }

    /**
     * @return ContextTypes
     */
    private function reflectClassAliasTypes(PhpDoc $phpDoc): array
    {
        $types = [];

        foreach ($phpDoc->typeAliases() as $typeAlias) {
            $types[$typeAlias->alias] = function () use ($typeAlias): Type {
                /** @var ?Type $type */
                static $type = null;

                return $type ??= $this->phpDocTypeReflector->reflect($typeAlias->type);
            };
        }

        foreach ($phpDoc->typeAliasImports() as $typeImport) {
            $alias = $typeImport->importedAs ?? $typeImport->importedAlias;
            $types[$alias] = /** @param list<Type> $arguments */ function (array $arguments) use ($typeImport): Type {
                /** @var ?non-empty-string $class */
                static $class = null;
                $class ??= $this->resolveNameAsClass($typeImport->importedFrom);

                return types::alias($class, $typeImport->importedAlias, ...$arguments);
            };
        }

        return $types;
    }

    /**
     * @return ContextTypes
     */
    private function reflectTemplateTypes(AtClass|AtMethod $declaredAt, PhpDoc $phpDoc): array
    {
        $types = [];

        foreach ($phpDoc->templates() as $template) {
            $types[$template->name] = /** @param list<Type> $arguments */ static fn(array $arguments): Type => types::template($template->name, $declaredAt, ...$arguments);
        }

        return $types;
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
