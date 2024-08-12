<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpDoc;

use PhpParser\Comment\Doc;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassLike;
use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\PhpDoc\MethodTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\MethodTagValueParameterNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PropertyTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypeAliasImportTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypeAliasTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Annotated\CustomTypeResolver;
use Typhoon\Reflection\Annotated\NullCustomTypeResolver;
use Typhoon\Reflection\Deprecation;
use Typhoon\Reflection\Internal\Annotated\AnnotatedDeclarations;
use Typhoon\Reflection\Internal\Annotated\AnnotatedDeclarationsDiscoverer;
use Typhoon\Reflection\Internal\Context\Context;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Data\PassedBy;
use Typhoon\Reflection\Internal\Data\TypeData;
use Typhoon\Reflection\Internal\Data\Visibility;
use Typhoon\Reflection\Internal\Hook\ClassHook;
use Typhoon\Reflection\Internal\Hook\ConstantHook;
use Typhoon\Reflection\Internal\Hook\FunctionHook;
use Typhoon\Reflection\Location;
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\Type\Type;
use Typhoon\Type\types;
use Typhoon\Type\Variance;
use Typhoon\TypedMap\TypedMap;
use function Typhoon\Reflection\Internal\map;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class PhpDocReflector implements AnnotatedDeclarationsDiscoverer, ConstantHook, FunctionHook, ClassHook
{
    public function __construct(
        private readonly CustomTypeResolver $customTypeResolver = new NullCustomTypeResolver(),
        private readonly PhpDocParser $parser = new PhpDocParser(),
    ) {}

    public function discoverAnnotatedDeclarations(FunctionLike|ClassLike $node): AnnotatedDeclarations
    {
        $phpDoc = $this->parsePhpDoc($node->getDocComment());

        if ($phpDoc === null) {
            return new AnnotatedDeclarations();
        }

        return new AnnotatedDeclarations(
            templateNames: array_map(
                /** @param PhpDocTagNode<TemplateTagValueNode> $tag */
                static fn(PhpDocTagNode $tag): string => $tag->value->name,
                $phpDoc->templateTags(),
            ),
            aliasNames: [
                ...array_map(
                    /** @param PhpDocTagNode<TypeAliasTagValueNode> $tag */
                    static fn(PhpDocTagNode $tag): string => $tag->value->alias,
                    $phpDoc->typeAliasTags(),
                ),
                ...array_map(
                    /** @param PhpDocTagNode<TypeAliasImportTagValueNode> $tag */
                    static fn(PhpDocTagNode $tag): string => $tag->value->importedAs ?? $tag->value->importedAlias,
                    $phpDoc->typeAliasImportTags(),
                ),
            ],
        );
    }

    public function priority(): int
    {
        return 500;
    }

    public function processConstant(ConstantId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap
    {
        $phpDoc = $this->parsePhpDoc($data[Data::PhpDoc]);

        if ($phpDoc !== null) {
            $data = $data->with(Data::Type, $this->addAnnotatedType($data[Data::Context], $data[Data::Type], $phpDoc->varType()));
        }

        return $data;
    }

    public function processFunction(NamedFunctionId|AnonymousFunctionId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap
    {
        return $this->reflectFunctionLike($data);
    }

    private function reflectFunctionLike(TypedMap $data): TypedMap
    {
        $context = $data[Data::Context];
        $phpDoc = $this->parsePhpDoc($data[Data::PhpDoc]);

        if ($phpDoc !== null) {
            $data = $data
                ->with(Data::Deprecation, $this->reflectDeprecation($phpDoc->deprecatedMessage()))
                ->with(Data::Templates, $this->reflectTemplates($context, $phpDoc->templateTags()))
                ->with(Data::Type, $this->addAnnotatedType($context, $data[Data::Type], $phpDoc->returnType()))
                ->with(Data::ThrowsType, $this->reflectThrowsType($context, $phpDoc->throwsTypes()));
        }

        $paramTypes = $phpDoc?->paramTypes() ?? [];

        return $data->with(Data::Parameters, map(
            $data[Data::Parameters],
            function (TypedMap $parameter, string $name) use ($context, $paramTypes): TypedMap {
                $phpDoc = $this->parsePhpDoc($parameter[Data::PhpDoc]);

                return $parameter
                    ->with(Data::Deprecation, $this->reflectDeprecation($phpDoc?->deprecatedMessage()))
                    ->with(Data::AnnotatedReadonly, $parameter[Data::AnnotatedReadonly] || ($phpDoc?->hasReadonly() ?? false))
                    ->with(Data::Type, $this->addAnnotatedType($context, $parameter[Data::Type], $phpDoc?->varType() ?? $paramTypes[$name] ?? null));
            },
        ));
    }

    public function processClass(NamedClassId|AnonymousClassId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap
    {
        $context = $data[Data::Context];

        $data = $data
            ->with(Data::UnresolvedTraits, $this->reflectUses($context, $data))
            ->with(Data::Constants, array_map(
                fn(TypedMap $constant): TypedMap => $this->reflectNativeConstant($context, $constant),
                $data[Data::Constants],
            ))
            ->with(Data::Properties, array_map(
                fn(TypedMap $property): TypedMap => $this->reflectNativeProperty($context, $property),
                $data[Data::Properties],
            ))
            ->with(Data::Methods, array_map(
                fn(TypedMap $method): TypedMap => $this->reflectFunctionLike($method),
                $data[Data::Methods],
            ));

        $phpDoc = $this->parsePhpDoc($data[Data::PhpDoc]);

        if ($phpDoc === null) {
            return $data;
        }

        return $data
            ->with(Data::Deprecation, $this->reflectDeprecation($phpDoc->deprecatedMessage()))
            ->with(Data::AnnotatedFinal, $data[Data::AnnotatedFinal] || $phpDoc->hasFinal())
            ->with(Data::AnnotatedFinal, $data[Data::AnnotatedReadonly] || $phpDoc->hasReadonly())
            ->with(Data::Templates, $this->reflectTemplates($context, $phpDoc->templateTags()))
            ->with(Data::Aliases, $this->reflectAliases($context, $phpDoc))
            ->with(Data::UnresolvedParent, $this->reflectParent($context, $data, $phpDoc))
            ->with(Data::UnresolvedInterfaces, $this->reflectInterfaces($context, $data, $phpDoc))
            ->with(Data::Properties, [
                ...$data[Data::Properties],
                ...$this->reflectPhpDocProperties($context, $phpDoc->propertyTags()),
            ])
            ->with(Data::Methods, [
                ...$data[Data::Methods],
                ...$this->reflectPhpDocMethods($context, $phpDoc->methodTags()),
            ]);
    }

    /**
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectAliases(Context $context, PhpDoc $phpDoc): array
    {
        $typeReflector = new PhpDocTypeReflector($context, $this->customTypeResolver);
        $aliases = [];

        foreach ($phpDoc->typeAliasTags() as $tag) {
            $aliases[$tag->value->alias] = (new TypedMap())
                ->with(Data::Location, $this->reflectLocation($context, $tag))
                ->with(Data::AliasType, $typeReflector->reflectType($tag->value->type));
        }

        foreach ($phpDoc->typeAliasImportTags() as $tag) {
            $aliases[$tag->value->importedAs ?? $tag->value->importedAlias] = (new TypedMap())
                ->with(Data::Location, $this->reflectLocation($context, $tag))
                ->with(Data::AliasType, types::classAlias(
                    class: $typeReflector->resolveClass($tag->value->importedFrom),
                    name: $tag->value->importedAlias,
                ));
        }

        return $aliases;
    }

    /**
     * @param list<PhpDocTagNode<TemplateTagValueNode>> $tags
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectTemplates(Context $context, array $tags): array
    {
        $typeReflector = new PhpDocTypeReflector($context, $this->customTypeResolver);
        $templates = [];

        foreach ($tags as $tag) {
            $templates[$tag->value->name] = (new TypedMap())
                ->with(Data::Location, $this->reflectLocation($context, $tag))
                ->with(Data::Constraint, $typeReflector->reflectType($tag->value->bound) ?? types::mixed)
                ->with(Data::Variance, match (true) {
                    str_ends_with($tag->name, 'covariant') => Variance::Covariant,
                    str_ends_with($tag->name, 'contravariant') => Variance::Contravariant,
                    default => Variance::Invariant,
                });
        }

        return $templates;
    }

    /**
     * @return ?array{non-empty-string, list<Type>}
     */
    private function reflectParent(Context $context, TypedMap $data, PhpDoc $phpDoc): ?array
    {
        $parent = $data[Data::UnresolvedParent];

        if ($parent === null) {
            return null;
        }

        $inheritedTypes = $this->reflectInheritedTypes($context, $phpDoc);

        if (isset($inheritedTypes[$parent[0]])) {
            return [$parent[0], $inheritedTypes[$parent[0]]];
        }

        return $parent;
    }

    /**
     * @return array<non-empty-string, list<Type>>
     */
    private function reflectInterfaces(Context $context, TypedMap $data, PhpDoc $phpDoc): array
    {
        $inheritedTypes = $this->reflectInheritedTypes($context, $phpDoc);

        return [
            ...$data[Data::UnresolvedInterfaces],
            ...array_intersect_key($inheritedTypes, $data[Data::UnresolvedInterfaces]),
        ];
    }

    /**
     * @return array<non-empty-string, list<Type>>
     */
    private function reflectUses(Context $context, TypedMap $data): array
    {
        $uses = $data[Data::UnresolvedTraits];

        if ($uses === []) {
            return [];
        }

        $typeReflector = new PhpDocTypeReflector($context, $this->customTypeResolver);

        foreach ($data[Data::UsePhpDocs] as $usePhpDoc) {
            $usePhpDoc = $this->parsePhpDoc($usePhpDoc);

            foreach ($usePhpDoc->usedTypes() as $type) {
                $name = $typeReflector->resolveClass($type->type);

                if (isset($uses[$name])) {
                    $uses[$name] = array_map($typeReflector->reflectType(...), $type->genericTypes);
                }
            }
        }

        return $uses;
    }

    private function reflectNativeConstant(Context $context, TypedMap $data): TypedMap
    {
        $phpDoc = $this->parsePhpDoc($data[Data::PhpDoc]);

        if ($phpDoc === null) {
            return $data;
        }

        return $data
            ->with(Data::Deprecation, $this->reflectDeprecation($phpDoc->deprecatedMessage()))
            ->with(Data::AnnotatedFinal, $data[Data::AnnotatedFinal] || $phpDoc->hasFinal())
            ->with(Data::Type, $this->addAnnotatedType($context, $data[Data::Type], $phpDoc->varType()));
    }

    private function reflectNativeProperty(Context $context, TypedMap $data): TypedMap
    {
        $phpDoc = $this->parsePhpDoc($data[Data::PhpDoc]);

        if ($phpDoc === null) {
            return $data;
        }

        return $data
            ->with(Data::Deprecation, $this->reflectDeprecation($phpDoc->deprecatedMessage()))
            ->with(Data::AnnotatedReadonly, $data[Data::AnnotatedReadonly] || $phpDoc->hasReadonly())
            ->with(Data::Type, $this->addAnnotatedType($context, $data[Data::Type], $phpDoc->varType()));
    }

    /**
     * @param list<PhpDocTagNode<PropertyTagValueNode>> $tags
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectPhpDocProperties(Context $context, array $tags): array
    {
        $typeReflector = new PhpDocTypeReflector($context, $this->customTypeResolver);
        $properties = [];

        foreach ($tags as $tag) {
            $name = ltrim($tag->value->propertyName, '$');

            if ($name === '') {
                continue;
            }

            $properties[$name] = (new TypedMap())
                ->with(Data::Location, $this->reflectLocation($context, $tag))
                ->with(Data::Annotated, true)
                ->with(Data::Visibility, Visibility::Public)
                ->with(Data::AnnotatedReadonly, str_contains($tag->name, 'read'))
                ->with(Data::Type, new TypeData(annotated: $typeReflector->reflectType($tag->value->type)));
        }

        return $properties;
    }

    /**
     * @param list<PhpDocTagNode<MethodTagValueNode>> $tags
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectPhpDocMethods(Context $classContext, array $tags): array
    {
        $methods = [];

        foreach ($tags as $tag) {
            $name = $tag->value->methodName;
            $context = $classContext->enterMethod($name, array_column($tag->value->templateTypes, 'name'));
            $typeReflector = new PhpDocTypeReflector($context, $this->customTypeResolver);
            $methods[$name] = (new TypedMap())
                ->with(Data::Location, $this->reflectLocation($context, $tag))
                ->with(Data::Context, $context)
                ->with(Data::Annotated, true)
                ->with(Data::Visibility, Visibility::Public)
                ->with(Data::Static, $tag->value->isStatic)
                ->with(Data::Type, new TypeData(annotated: $typeReflector->reflectType($tag->value->returnType)))
                ->with(Data::Templates, array_combine(
                    array_column($tag->value->templateTypes, 'name'),
                    array_map(
                        fn(TemplateTagValueNode $value): TypedMap => (new TypedMap())
                            ->with(Data::Location, $this->reflectLocation($context, $value))
                            ->with(Data::Constraint, $typeReflector->reflectType($value->bound) ?? types::mixed)
                            ->with(Data::Variance, Variance::Invariant),
                        $tag->value->templateTypes,
                    ),
                ))
                ->with(Data::Parameters, $this->reflectPhpDocMethodParameters($context, $tag->value->parameters));
        }

        return $methods;
    }

    /**
     * @param array<MethodTagValueParameterNode> $tags
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectPhpDocMethodParameters(Context $context, array $tags): array
    {
        $typeReflector = new PhpDocTypeReflector($context, $this->customTypeResolver);
        $compiler = new PhpDocConstantExpressionCompiler($context);
        $parameters = [];

        foreach ($tags as $tag) {
            $name = trim($tag->parameterName, '$');
            \assert($name !== '', 'Parameter name must not be empty');

            $parameters[$name] = (new TypedMap())
                ->with(Data::Annotated, true)
                ->with(Data::Location, $this->reflectLocation($context, $tag))
                ->with(Data::Type, new TypeData(annotated: $typeReflector->reflectType($tag->type)))
                ->with(Data::PassedBy, $tag->isReference ? PassedBy::Reference : PassedBy::Value)
                ->with(Data::Variadic, $tag->isVariadic)
                ->with(Data::DefaultValueExpression, $compiler->compile($tag->defaultValue));
        }

        return $parameters;
    }

    private function addAnnotatedType(Context $context, TypeData $type, ?TypeNode $node): TypeData
    {
        if ($node === null) {
            return $type;
        }

        $typeReflector = new PhpDocTypeReflector($context, $this->customTypeResolver);

        return $type->withAnnotated($typeReflector->reflectType($node));
    }

    private function reflectDeprecation(?string $message): ?Deprecation
    {
        if ($message === null) {
            return null;
        }

        return new Deprecation($message ?: null);
    }

    /**
     * @param list<TypeNode> $throwsTypes
     */
    private function reflectThrowsType(Context $context, array $throwsTypes): ?Type
    {
        if ($throwsTypes === []) {
            return null;
        }

        $typeReflector = new PhpDocTypeReflector($context, $this->customTypeResolver);

        return types::union(...array_map($typeReflector->reflectType(...), $throwsTypes));
    }

    private function reflectLocation(Context $context, Node $node): Location
    {
        $startPosition = PhpDocParser::startPosition($node);
        $endPosition = PhpDocParser::endPosition($node);

        return new Location(
            startPosition: $startPosition,
            endPosition: $endPosition,
            startLine: PhpDocParser::startLine($node),
            endLine: PhpDocParser::endLine($node),
            startColumn: $context->column($startPosition),
            endColumn: $context->column($endPosition),
        );
    }

    /**
     * @return ($doc is null ? null : PhpDoc)
     */
    private function parsePhpDoc(?Doc $doc): ?PhpDoc
    {
        if ($doc === null) {
            return null;
        }

        $startLine = $doc->getStartLine();
        \assert($startLine > 0);

        $startPosition = $doc->getStartFilePos();
        \assert($startPosition >= 0);

        return $this->parser->parse(
            phpDoc: $doc->getText(),
            startLine: $startLine,
            startPosition: $startPosition,
        );
    }

    /**
     * @return array<non-empty-string, list<Type>>
     */
    private function reflectInheritedTypes(Context $context, PhpDoc $phpDoc): array
    {
        $typeReflector = new PhpDocTypeReflector($context, $this->customTypeResolver);
        $namedObjectTypeDestructurizer = new NamedObjectTypeDestructurizer();

        $inheritedTypes = [];

        foreach ([...$phpDoc->extendedTypes(), ...$phpDoc->implementedTypes()] as $typeNode) {
            $destructurized = $typeReflector->reflectType($typeNode)->accept($namedObjectTypeDestructurizer);

            if ($destructurized === null) {
                continue;
            }

            [$classId, $typeArguments] = $destructurized;

            if ($classId instanceof AnonymousClassId) {
                continue;
            }

            $inheritedTypes[$classId->name] = $typeArguments;
        }

        return $inheritedTypes;
    }
}
