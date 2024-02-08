<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpParserReflector;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\FileResource;
use Typhoon\Reflection\Metadata\AttributeMetadata;
use Typhoon\Reflection\Metadata\ClassMetadata;
use Typhoon\Reflection\Metadata\MethodMetadata;
use Typhoon\Reflection\Metadata\ParameterMetadata;
use Typhoon\Reflection\Metadata\PropertyMetadata;
use Typhoon\Reflection\Metadata\TypeMetadata;
use Typhoon\Reflection\PhpDocParser\ContextualPhpDocTypeReflector;
use Typhoon\Reflection\PhpDocParser\PhpDoc;
use Typhoon\Reflection\PhpDocParser\PhpDocParser;
use Typhoon\Reflection\ReflectionException;
use Typhoon\Reflection\TemplateReflection;
use Typhoon\Reflection\TypeAlias\ImportedTypeAlias;
use Typhoon\Reflection\TypeContext\TypeContext;
use Typhoon\Type;
use Typhoon\Type\types;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\PhpParserReflector
 */
final class ContextualPhpParserReflector
{
    private ContextualPhpDocTypeReflector $phpDocTypeReflector;

    public function __construct(
        private readonly PhpDocParser $phpDocParser,
        private TypeContext $typeContext,
        private readonly FileResource $file,
    ) {
        $this->phpDocTypeReflector = new ContextualPhpDocTypeReflector($typeContext);
    }

    /**
     * @return class-string
     */
    public function resolveClassName(Node\Identifier $name): string
    {
        return $this->typeContext->resolveNameAsClass($name->name);
    }

    /**
     * @template TObject of object
     * @param class-string<TObject> $name
     * @return ClassMetadata<TObject>
     */
    public function reflectClass(Stmt\ClassLike $node, string $name): ClassMetadata
    {
        $phpDoc = $this->parsePhpDoc($node);
        $startLine = CorrectClassStartLineVisitor::getStartLine($node);

        return $this->executeWithTypes(types::atClass($name), $phpDoc, fn(): ClassMetadata => new ClassMetadata(
            changeDetector: $this->file->changeDetector(),
            name: $name,
            internal: $this->file->isInternal(),
            extension: $this->file->extension,
            file: $this->file->isInternal() ? false : $this->file->file,
            startLine: $this->reflectLine($startLine),
            endLine: $this->reflectLine($node->getEndLine()),
            docComment: $this->reflectDocComment($node),
            attributes: $this->reflectAttributes($node->attrGroups, \Attribute::TARGET_CLASS),
            typeAliases: $this->reflectTypeAliasesFromContext($phpDoc),
            templates: $this->reflectTemplatesFromContext($phpDoc),
            interface: $node instanceof Stmt\Interface_,
            enum: $node instanceof Stmt\Enum_,
            trait: $node instanceof Stmt\Trait_,
            modifiers: $this->reflectClassModifiers($node),
            anonymous: $node->name === null,
            deprecated: $phpDoc->isDeprecated(),
            parentType: $this->reflectParent($node, $phpDoc),
            ownInterfaceTypes: $this->reflectOwnInterfaceTypes($node, $phpDoc),
            ownProperties: $this->reflectOwnProperties(class: $name, classNode: $node),
            ownMethods: $this->reflectOwnMethods(class: $name, classNode: $node),
        ));
    }

    public function __clone()
    {
        $this->typeContext = clone $this->typeContext;
        $this->phpDocTypeReflector = new ContextualPhpDocTypeReflector($this->typeContext);
    }

    /**
     * @param class-string $class
     */
    private function reflectEnumNameProperty(string $class): PropertyMetadata
    {
        return new PropertyMetadata(
            name: 'name',
            class: $class,
            docComment: false,
            hasDefaultValue: false,
            promoted: false,
            modifiers: \ReflectionProperty::IS_PUBLIC + \ReflectionProperty::IS_READONLY,
            deprecated: false,
            type: TypeMetadata::create(native: types::string, phpDoc: types::nonEmptyString),
            startLine: false,
            endLine: false,
            attributes: [],
        );
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
                $name = $this->typeContext->resolveNameAsClass($attr->name->toCodeString());

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

    /**
     * @return int-mask-of<\ReflectionClass::IS_*>
     */
    private function reflectClassModifiers(Stmt\ClassLike $node): int
    {
        if ($node instanceof Stmt\Enum_) {
            return ClassReflection::IS_FINAL;
        }

        if (!$node instanceof Stmt\Class_) {
            return 0;
        }

        $modifiers = ($node->isAbstract() ? ClassReflection::IS_EXPLICIT_ABSTRACT : 0)
            + ($node->isFinal() ? ClassReflection::IS_FINAL : 0);

        if (\defined(\ReflectionClass::class . '::IS_READONLY') && $node->isReadonly()) {
            /**
             * @var int-mask-of<\ReflectionClass::IS_*>
             * @psalm-suppress MixedOperand, UnusedPsalmSuppress
             */
            $modifiers += \ReflectionClass::IS_READONLY;
        }

        return $modifiers;
    }

    private function reflectParent(Stmt\ClassLike $node, PhpDoc $phpDoc): ?Type\NamedObjectType
    {
        if (!$node instanceof Stmt\Class_ || $node->extends === null) {
            return null;
        }

        $parentClass = $this->typeContext->resolveNameAsClass($node->extends->toCodeString());

        foreach ($phpDoc->extendedTypes() as $phpDocExtendedType) {
            /** @var Type\NamedObjectType $extendedType */
            $extendedType = $this->safelyReflectPhpDocType($phpDocExtendedType);

            if ($extendedType->class === $parentClass) {
                return $extendedType;
            }
        }

        return types::object($parentClass);
    }

    /**
     * @return list<Type\NamedObjectType>
     */
    private function reflectOwnInterfaceTypes(Stmt\ClassLike $node, PhpDoc $phpDoc): array
    {
        if ($node instanceof Stmt\Interface_) {
            $interfaceNames = $node->extends;
            $phpDocInterfaceTypes = $phpDoc->extendedTypes();
        } elseif ($node instanceof Stmt\Class_) {
            $interfaceNames = $node->implements;
            $phpDocInterfaceTypes = $phpDoc->implementedTypes();
        } elseif ($node instanceof Stmt\Enum_) {
            $interfaceNames = [
                ...$node->implements,
                new Name\FullyQualified(\UnitEnum::class),
                ...($node->scalarType === null ? [] : [new Name\FullyQualified(\BackedEnum::class)]),
            ];
            $phpDocInterfaceTypes = $phpDoc->implementedTypes();
        } else {
            return [];
        }

        if ($interfaceNames === []) {
            return [];
        }

        $phpDocInterfaceTypesByClass = [];

        foreach ($phpDocInterfaceTypes as $phpDocInterfaceType) {
            /** @var Type\NamedObjectType $implementedType */
            $implementedType = $this->safelyReflectPhpDocType($phpDocInterfaceType);
            $phpDocInterfaceTypesByClass[$implementedType->class] = $implementedType;
        }

        $reflectedInterfaceTypes = [];

        foreach ($interfaceNames as $interfaceName) {
            $interfaceNameAsString = $interfaceName->toCodeString();

            // https://github.com/phpstan/phpstan/issues/8889
            if (\in_array($interfaceNameAsString, ['iterable', 'callable'], true)) {
                continue;
            }

            $interface = $this->typeContext->resolveNameAsClass($interfaceNameAsString);
            $reflectedInterfaceTypes[] = $phpDocInterfaceTypesByClass[$interface] ?? types::object($interface);
        }

        return $reflectedInterfaceTypes;
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
            $properties[] = $this->reflectEnumNameProperty($class);

            if ($classNode->scalarType !== null) {
                $properties[] = $this->reflectBackedEnumValueProperty($class, $classNode->scalarType);
            }
        }

        foreach ($classNode->getProperties() as $node) {
            $phpDoc = $this->parsePhpDoc($node);
            $type = $this->reflectType($node->type, $phpDoc->varType());

            foreach ($node->props as $property) {
                $properties[] = new PropertyMetadata(
                    name: $property->name->name,
                    class: $class,
                    docComment: $this->reflectDocComment($node),
                    hasDefaultValue: $property->default !== null || $node->type === null,
                    promoted: false,
                    modifiers: $this->reflectPropertyModifiers($node, $classReadOnly),
                    deprecated: $phpDoc->isDeprecated(),
                    type: $type,
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
            $modifiers = $this->reflectPromotedPropertyModifiers($node, $classReadOnly);

            if ($modifiers === 0) {
                continue;
            }

            \assert($node->var instanceof Expr\Variable && \is_string($node->var->name));
            $name = $node->var->name;
            $properties[] = new PropertyMetadata(
                name: $name,
                class: $class,
                docComment: $this->reflectDocComment($node),
                hasDefaultValue: $node->default !== null || $node->type === null,
                promoted: true,
                modifiers: $modifiers,
                deprecated: $phpDoc->isDeprecated(),
                type: $this->reflectType($node->type, $phpDoc->paramTypes()[$name] ?? null),
                startLine: $this->reflectLine($node->getStartLine()),
                endLine: $this->reflectLine($node->getEndLine()),
                attributes: $this->reflectAttributes($node->attrGroups, \Attribute::TARGET_PROPERTY),
            );
        }

        return $properties;
    }

    /**
     * @return int-mask-of<\ReflectionProperty::IS_*>
     */
    private function reflectPropertyModifiers(Stmt\Property $node, bool $classReadOnly): int
    {
        return ($node->isStatic() ? \ReflectionProperty::IS_STATIC : 0)
            + ($node->isPublic() ? \ReflectionProperty::IS_PUBLIC : 0)
            + ($node->isProtected() ? \ReflectionProperty::IS_PROTECTED : 0)
            + ($node->isPrivate() ? \ReflectionProperty::IS_PRIVATE : 0)
            + ($classReadOnly || $node->isReadonly() ? \ReflectionProperty::IS_READONLY : 0);
    }

    /**
     * @return int-mask-of<\ReflectionProperty::IS_*>
     */
    private function reflectPromotedPropertyModifiers(Node\Param $node, bool $classReadOnly): int
    {
        return (($node->flags & Stmt\Class_::MODIFIER_PUBLIC) !== 0 ? \ReflectionProperty::IS_PUBLIC : 0)
            + (($node->flags & Stmt\Class_::MODIFIER_PROTECTED) !== 0 ? \ReflectionProperty::IS_PROTECTED : 0)
            + (($node->flags & Stmt\Class_::MODIFIER_PRIVATE) !== 0 ? \ReflectionProperty::IS_PRIVATE : 0)
            + (($classReadOnly || ($node->flags & Stmt\Class_::MODIFIER_READONLY) !== 0) ? \ReflectionProperty::IS_READONLY : 0);
    }

    /**
     * @return int-mask-of<\ReflectionMethod::IS_*>
     */
    private function reflectMethodModifiers(Stmt\ClassMethod $node, bool $interface): int
    {
        return ($node->isStatic() ? \ReflectionMethod::IS_STATIC : 0)
            + ($node->isPublic() ? \ReflectionMethod::IS_PUBLIC : 0)
            + ($node->isProtected() ? \ReflectionMethod::IS_PROTECTED : 0)
            + ($node->isPrivate() ? \ReflectionMethod::IS_PRIVATE : 0)
            + (($interface || $node->isAbstract()) ? \ReflectionMethod::IS_ABSTRACT : 0)
            + ($node->isFinal() ? \ReflectionMethod::IS_FINAL : 0);
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
                templates: $this->reflectTemplatesFromContext($phpDoc),
                modifiers: $this->reflectMethodModifiers($node, $interface),
                docComment: $this->reflectDocComment($node),
                internal: $this->file->isInternal(),
                extension: $this->file->extension,
                file: $this->file->isInternal() ? false : $this->file->file,
                startLine: $this->reflectLine($node->getStartLine()),
                endLine: $this->reflectLine($node->getEndLine()),
                returnsReference: $node->byRef,
                generator: $this->reflectIsGenerator($node),
                deprecated: $phpDoc->isDeprecated(),
                parameters: $this->reflectParameters($node->params, $phpDoc, $class, $name),
                returnType: $this->reflectType($node->returnType, $phpDoc->returnType()),
                attributes: $this->reflectAttributes($node->attrGroups, \Attribute::TARGET_METHOD),
            ));
        }

        if ($classNode instanceof Stmt\Enum_) {
            $methods[] = $this->reflectEnumCasesMethod($class);

            if ($classNode->scalarType !== null) {
                $methods = [...$methods, ...$this->reflectBackedEnumMethods($class, $classNode->scalarType)];
            }
        }

        return $methods;
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

    private function reflectIsGenerator(Stmt\ClassMethod $node): bool
    {
        $traverser = new NodeTraverser();
        $visitor = new class () extends NodeVisitorAbstract {
            /**
             * @psalm-readonly-allow-private-mutation
             */
            public bool $hasYield = false;

            public function enterNode(Node $node): ?int
            {
                if ($node instanceof Yield_) {
                    $this->hasYield = true;

                    return NodeTraverser::STOP_TRAVERSAL;
                }

                return null;
            }
        };
        $traverser->addVisitor($visitor);
        $traverser->traverse([$node]);

        return $visitor->hasYield;
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
                passedByReference: $node->byRef,
                defaultValueAvailable: $node->default !== null,
                optional: $isOptional,
                variadic: $node->variadic,
                promoted: $this->isParameterPromoted($node),
                deprecated: $phpDoc->isDeprecated(),
                type: $this->reflectType($node->type, $methodPhpDoc->paramTypes()[$name] ?? null, $this->isParamDefaultNull($node)),
                startLine: $this->reflectLine($node->getStartLine()),
                endLine: $this->reflectLine($node->getEndLine()),
                attributes: $this->reflectAttributes($node->attrGroups, \Attribute::TARGET_PARAMETER),
            );
        }

        return $parameters;
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

    private function reflectType(?Node $native, ?TypeNode $phpDoc, bool $implicitlySupportsNull = false): TypeMetadata
    {
        return TypeMetadata::create(
            native: $this->safelyReflectNativeType($native, $implicitlySupportsNull),
            phpDoc: $this->safelyReflectPhpDocType($phpDoc),
        );
    }

    /**
     * @todo Add try/catch + logging.
     */
    private function safelyReflectNativeType(?Node $node, bool $implicitlySupportsNull = false): ?Type\Type
    {
        return $this->reflectNativeType($node, $implicitlySupportsNull);
    }

    /**
     * @return ($node is null ? null : Type\Type)
     */
    private function reflectNativeType(?Node $node, bool $implicitlySupportsNull = false): ?Type\Type
    {
        if ($node === null) {
            return null;
        }

        if ($node instanceof Node\NullableType) {
            return types::nullable($this->reflectNativeType($node->type));
        }

        if ($implicitlySupportsNull) {
            return types::nullable($this->reflectNativeType($node));
        }

        if ($node instanceof Node\UnionType) {
            return types::union(...array_map(
                fn(Node $child): Type\Type => $this->reflectNativeType($child),
                $node->types,
            ));
        }

        if ($node instanceof Node\IntersectionType) {
            return types::intersection(...array_map(
                fn(Node $child): Type\Type => $this->reflectNativeType($child),
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
                'iterable' => types::iterable(),
                'resource' => types::resource,
                'mixed' => types::mixed,
                default => throw new ReflectionException(sprintf(
                    '%s with name "%s" is not supported.',
                    $node->name,
                    $node::class,
                )),
            };
        }

        if ($node instanceof Name) {
            $resolvedName = $this->typeContext->resolveNameAsClass($node->toCodeString());

            if ($node->toString() === 'static') {
                return types::static($resolvedName);
            }

            return types::object($resolvedName);
        }

        throw new ReflectionException(sprintf('%s is not supported.', $node::class));
    }

    /**
     * @todo Add try/catch + logging.
     */
    private function safelyReflectPhpDocType(?TypeNode $node): ?Type\Type
    {
        if ($node === null) {
            return null;
        }

        return $this->phpDocTypeReflector->reflect($node);
    }

    private function isParameterPromoted(Node\Param $node): bool
    {
        return ($node->flags & Stmt\Class_::MODIFIER_PUBLIC) !== 0
            || ($node->flags & Stmt\Class_::MODIFIER_PROTECTED) !== 0
            || ($node->flags & Stmt\Class_::MODIFIER_PRIVATE) !== 0;
    }

    private function isParamDefaultNull(Node\Param $param): bool
    {
        return $param->default instanceof Expr\ConstFetch && $param->default->name->toCodeString() === 'null';
    }

    /**
     * @param class-string $class
     */
    private function reflectBackedEnumValueProperty(string $class, Node\Identifier $scalarType): PropertyMetadata
    {
        return new PropertyMetadata(
            name: 'value',
            class: $class,
            docComment: false,
            hasDefaultValue: false,
            promoted: false,
            modifiers: \ReflectionProperty::IS_PUBLIC + \ReflectionProperty::IS_READONLY,
            deprecated: false,
            type: $this->reflectType($scalarType, null),
            startLine: false,
            endLine: false,
            attributes: [],
        );
    }

    /**
     * @param class-string $class
     */
    private function reflectEnumCasesMethod(string $class): MethodMetadata
    {
        return new MethodMetadata(
            name: 'cases',
            class: $class,
            templates: [],
            modifiers: \ReflectionMethod::IS_STATIC + \ReflectionMethod::IS_PUBLIC,
            docComment: false,
            internal: true,
            extension: false,
            file: false,
            startLine: false,
            endLine: false,
            returnsReference: false,
            generator: false,
            deprecated: false,
            parameters: [],
            returnType: TypeMetadata::create(types::array(), types::list(types::object($class))),
            attributes: [],
        );
    }

    /**
     * @param class-string $class
     * @return list<MethodMetadata>
     */
    private function reflectBackedEnumMethods(string $class, Node\Identifier $scalarType): array
    {
        $valueType = $this->reflectType($scalarType, null);

        return [
            new MethodMetadata(
                name: 'from',
                class: $class,
                templates: [],
                modifiers: \ReflectionMethod::IS_STATIC + \ReflectionMethod::IS_PUBLIC,
                docComment: false,
                internal: true,
                extension: false,
                file: false,
                startLine: false,
                endLine: false,
                returnsReference: false,
                generator: false,
                deprecated: false,
                parameters: [
                    new ParameterMetadata(
                        position: 0,
                        name: 'value',
                        class: $class,
                        functionOrMethod: 'from',
                        passedByReference: false,
                        defaultValueAvailable: false,
                        optional: false,
                        variadic: false,
                        promoted: false,
                        deprecated: false,
                        type: $valueType,
                        startLine: false,
                        endLine: false,
                        attributes: [],
                    ),
                ],
                returnType: TypeMetadata::create(types::array(), types::list(types::object($class))),
                attributes: [],
            ),
            new MethodMetadata(
                name: 'tryFrom',
                class: $class,
                templates: [],
                modifiers: \ReflectionMethod::IS_STATIC + \ReflectionMethod::IS_PUBLIC,
                docComment: false,
                internal: true,
                extension: false,
                file: false,
                startLine: false,
                endLine: false,
                returnsReference: false,
                generator: false,
                deprecated: false,
                parameters: [
                    new ParameterMetadata(
                        position: 0,
                        name: 'value',
                        class: $class,
                        functionOrMethod: 'tryFrom',
                        passedByReference: false,
                        defaultValueAvailable: false,
                        optional: false,
                        variadic: false,
                        promoted: false,
                        deprecated: false,
                        type: $valueType,
                        startLine: false,
                        endLine: false,
                        attributes: [],
                    ),
                ],
                returnType: TypeMetadata::create(
                    types::nullable(types::array()),
                    types::nullable(types::list(types::object($class))),
                ),
                attributes: [],
            ),
        ];
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
            $types[$typeAlias->alias] = fn(): Type\Type => $this->safelyReflectPhpDocType($typeAlias->type) ?? types::mixed;
        }

        foreach ($phpDoc->typeAliasImports() as $typeImport) {
            $alias = $typeImport->importedAs ?? $typeImport->importedAlias;
            $types[$alias] = function () use ($class, $typeImport): Type\Type {
                $fromClass = $this->typeContext->resolveNameAsClass($typeImport->importedFrom->name);

                if ($fromClass === $class) {
                    return $this->typeContext->resolveNameAsType($typeImport->importedAlias);
                }

                return new ImportedTypeAlias($fromClass, $typeImport->importedAlias);
            };
        }

        foreach ($phpDoc->templates() as $template) {
            $types[$template->name] = fn(): Type\TemplateType => types::template(
                name: $template->name,
                declaredAt: $declaredAt,
                constraint: $this->safelyReflectPhpDocType($template->bound) ?? types::mixed,
            );
        }

        return $this->typeContext->executeWithTypes($action, $types);
    }

    private function parsePhpDoc(Node $node): PhpDoc
    {
        $text = $node->getDocComment()?->getText();

        if ($text === null || $text === '') {
            return PhpDoc::empty();
        }

        return $this->phpDocParser->parsePhpDoc($text);
    }
}
