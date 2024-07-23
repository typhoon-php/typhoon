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
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Deprecation;
use Typhoon\Reflection\Internal\ClassHook;
use Typhoon\Reflection\Internal\Context\AnnotatedTypeNames;
use Typhoon\Reflection\Internal\Context\AnnotatedTypesDriver;
use Typhoon\Reflection\Internal\Context\Context;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Data\ClassKind;
use Typhoon\Reflection\Internal\Data\TypeData;
use Typhoon\Reflection\Internal\Data\Visibility;
use Typhoon\Reflection\Internal\FunctionHook;
use Typhoon\Reflection\Internal\Reflector;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;
use Typhoon\Reflection\Location;
use Typhoon\Type\Type;
use Typhoon\Type\types;
use Typhoon\Type\Variance;
use function Typhoon\Reflection\Internal\column;
use function Typhoon\Reflection\Internal\map;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class PhpDocReflector implements AnnotatedTypesDriver, ClassHook, FunctionHook
{
    public function __construct(
        private readonly PhpDocParser $parser = new PhpDocParser(),
    ) {}

    public function reflectAnnotatedTypeNames(FunctionLike|ClassLike $node): AnnotatedTypeNames
    {
        $phpDoc = $this->parsePhpDoc($node->getDocComment());

        if ($phpDoc === null) {
            return new AnnotatedTypeNames();
        }

        return new AnnotatedTypeNames(
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

    public function process(NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $id, TypedMap $data, Reflector $reflector): TypedMap
    {
        if ($id instanceof NamedFunctionId || $id instanceof AnonymousFunctionId) {
            return $this->reflectFunctionLike($data[Data::Code], $data);
        }

        return $this->reflectClass($data[Data::Code], $data);
    }

    private function reflectFunctionLike(string $code, TypedMap $data): TypedMap
    {
        $typeReflector = new PhpDocTypeReflector($data[Data::Context]);
        $phpDoc = $this->parsePhpDoc($data[Data::PhpDoc]);

        if ($phpDoc !== null) {
            $data = $data
                ->with(Data::Deprecation, $this->reflectDeprecation($phpDoc->deprecatedMessage()))
                ->with(Data::Templates, $this->reflectTemplates($code, $typeReflector, $phpDoc->templateTags()))
                ->with(Data::Type, $this->addAnnotatedType($typeReflector, $data[Data::Type], $phpDoc->returnType()))
                ->with(Data::ThrowsType, $this->reflectThrowsType($typeReflector, $phpDoc->throwsTypes()));
        }

        $paramTypes = $phpDoc?->paramTypes() ?? [];

        return $data->with(Data::Parameters, map(
            $data[Data::Parameters],
            function (TypedMap $parameter, string $name) use ($typeReflector, $paramTypes): TypedMap {
                $phpDoc = $this->parsePhpDoc($parameter[Data::PhpDoc]);

                return $parameter
                    ->with(Data::Deprecation, $this->reflectDeprecation($phpDoc?->deprecatedMessage()))
                    ->with(Data::AnnotatedReadonly, $parameter[Data::AnnotatedReadonly] || ($phpDoc?->hasReadonly() ?? false))
                    ->with(Data::Type, $this->addAnnotatedType($typeReflector, $parameter[Data::Type], $phpDoc?->varType() ?? $paramTypes[$name] ?? null));
            },
        ));
    }

    private function reflectClass(string $code, TypedMap $data): TypedMap
    {
        $typeReflector = new PhpDocTypeReflector($data[Data::Context]);

        $data = $data
            ->with(Data::Constants, array_map(
                fn(TypedMap $constant): TypedMap => $this->reflectNativeConstant($typeReflector, $constant),
                $data[Data::Constants],
            ))
            ->with(Data::Properties, array_map(
                fn(TypedMap $property): TypedMap => $this->reflectNativeProperty($typeReflector, $property),
                $data[Data::Properties],
            ))
            ->with(Data::Methods, array_map(
                fn(TypedMap $method): TypedMap => $this->reflectFunctionLike($code, $method),
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
            ->with(Data::Templates, $this->reflectTemplates($code, $typeReflector, $phpDoc->templateTags()))
            ->with(Data::Aliases, $this->reflectAliases($code, $typeReflector, $phpDoc))
            ->with(Data::UnresolvedParent, $this->reflectParent($typeReflector, $data, $phpDoc))
            ->with(Data::UnresolvedInterfaces, $this->reflectInterfaces($typeReflector, $data, $phpDoc))
            ->with(Data::UnresolvedTraits, $this->reflectUses($typeReflector, $data))
            ->with(Data::Properties, [
                ...$data[Data::Properties],
                ...$this->reflectPhpDocProperties($code, $typeReflector, $phpDoc->propertyTags()),
            ])
            ->with(Data::Methods, [
                ...$data[Data::Methods],
                ...$this->reflectPhpDocMethods($code, $data[Data::Context], $phpDoc->methodTags()),
            ]);
    }

    /**
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectAliases(string $code, PhpDocTypeReflector $typeReflector, PhpDoc $phpDoc): array
    {
        $aliases = [];

        foreach ($phpDoc->typeAliasTags() as $tag) {
            $aliases[$tag->value->alias] = (new TypedMap())
                ->with(Data::Location, $this->reflectLocation($code, $tag))
                ->with(Data::AliasType, $typeReflector->reflectType($tag->value->type));
        }

        foreach ($phpDoc->typeAliasImportTags() as $tag) {
            $aliases[$tag->value->importedAs ?? $tag->value->importedAlias] = (new TypedMap())
                ->with(Data::Location, $this->reflectLocation($code, $tag))
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
    private function reflectTemplates(string $code, PhpDocTypeReflector $typeReflector, array $tags): array
    {
        $templates = [];

        foreach ($tags as $tag) {
            $templates[$tag->value->name] = (new TypedMap())
                ->with(Data::Location, $this->reflectLocation($code, $tag))
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
    private function reflectParent(PhpDocTypeReflector $typeReflector, TypedMap $data, PhpDoc $phpDoc): ?array
    {
        $parent = $data[Data::UnresolvedParent];

        if ($parent === null) {
            return null;
        }

        foreach ($phpDoc->extendedTypes() as $type) {
            if ($parent[0] === $typeReflector->resolveClass($type->type)) {
                $parent[1] = array_map($typeReflector->reflectType(...), $type->genericTypes);
            }
        }

        return $parent;
    }

    /**
     * @return array<non-empty-string, list<Type>>
     */
    private function reflectInterfaces(PhpDocTypeReflector $typeReflector, TypedMap $data, PhpDoc $phpDoc): array
    {
        $interfaces = $data[Data::UnresolvedInterfaces];

        if ($interfaces === []) {
            return [];
        }

        $types = $data[Data::ClassKind] === ClassKind::Interface ? $phpDoc->extendedTypes() : $phpDoc->implementedTypes();

        foreach ($types as $type) {
            $name = $typeReflector->resolveClass($type->type);

            if (isset($interfaces[$name])) {
                $interfaces[$name] = array_map($typeReflector->reflectType(...), $type->genericTypes);
            }
        }

        return $interfaces;
    }

    /**
     * @return array<non-empty-string, list<Type>>
     */
    private function reflectUses(PhpDocTypeReflector $typeReflector, TypedMap $data): array
    {
        $uses = $data[Data::UnresolvedTraits];

        if ($uses === []) {
            return [];
        }

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

    private function reflectNativeConstant(PhpDocTypeReflector $typeReflector, TypedMap $data): TypedMap
    {
        $phpDoc = $this->parsePhpDoc($data[Data::PhpDoc]);

        if ($phpDoc === null) {
            return $data;
        }

        return $data
            ->with(Data::Deprecation, $this->reflectDeprecation($phpDoc->deprecatedMessage()))
            ->with(Data::AnnotatedFinal, $data[Data::AnnotatedFinal] || $phpDoc->hasFinal())
            ->with(Data::Type, $this->addAnnotatedType($typeReflector, $data[Data::Type], $phpDoc->varType()));
    }

    private function reflectNativeProperty(PhpDocTypeReflector $typeReflector, TypedMap $data): TypedMap
    {
        $phpDoc = $this->parsePhpDoc($data[Data::PhpDoc]);

        if ($phpDoc === null) {
            return $data;
        }

        return $data
            ->with(Data::Deprecation, $this->reflectDeprecation($phpDoc->deprecatedMessage()))
            ->with(Data::AnnotatedReadonly, $data[Data::AnnotatedReadonly] || $phpDoc->hasReadonly())
            ->with(Data::Type, $this->addAnnotatedType($typeReflector, $data[Data::Type], $phpDoc->varType()));
    }

    /**
     * @param list<PhpDocTagNode<PropertyTagValueNode>> $tags
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectPhpDocProperties(string $code, PhpDocTypeReflector $typeReflector, array $tags): array
    {
        $properties = [];

        foreach ($tags as $tag) {
            $name = ltrim($tag->value->propertyName, '$');

            if ($name === '') {
                continue;
            }

            $properties[$name] = (new TypedMap())
                ->with(Data::Location, $this->reflectLocation($code, $tag))
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
    private function reflectPhpDocMethods(string $code, Context $classContext, array $tags): array
    {
        $methods = [];

        foreach ($tags as $tag) {
            $name = $tag->value->methodName;
            $context = $classContext->enterMethod($name, array_column($tag->value->templateTypes, 'name'));
            $typeReflector = new PhpDocTypeReflector($context);
            $methods[$name] = (new TypedMap())
                ->with(Data::Location, $this->reflectLocation($code, $tag))
                ->with(Data::Context, $context)
                ->with(Data::Annotated, true)
                ->with(Data::Visibility, Visibility::Public)
                ->with(Data::Static, $tag->value->isStatic)
                ->with(Data::Type, new TypeData(annotated: $typeReflector->reflectType($tag->value->returnType)))
                ->with(Data::Templates, array_combine(
                    array_column($tag->value->templateTypes, 'name'),
                    array_map(
                        fn(TemplateTagValueNode $value): TypedMap => (new TypedMap())
                            ->with(Data::Location, $this->reflectLocation($code, $value))
                            ->with(Data::Constraint, $typeReflector->reflectType($value->bound) ?? types::mixed)
                            ->with(Data::Variance, Variance::Invariant),
                        $tag->value->templateTypes,
                    ),
                ))
                ->with(Data::Parameters, $this->reflectPhpDocMethodParameters($code, $context, $typeReflector, $tag->value->parameters));
        }

        return $methods;
    }

    /**
     * @param array<MethodTagValueParameterNode> $tags
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectPhpDocMethodParameters(string $code, Context $context, PhpDocTypeReflector $typeReflector, array $tags): array
    {
        $compiler = new PhpDocConstantExpressionCompiler($context);
        $parameters = [];

        foreach ($tags as $tag) {
            $name = trim($tag->parameterName, '$');
            \assert($name !== '', 'Parameter name must not be empty');

            $parameters[$name] = (new TypedMap())
                ->with(Data::Annotated, true)
                ->with(Data::Location, $this->reflectLocation($code, $tag))
                ->with(Data::Type, new TypeData(annotated: $typeReflector->reflectType($tag->type)))
                ->with(Data::ByReference, $tag->isReference)
                ->with(Data::Variadic, $tag->isVariadic)
                ->with(Data::DefaultValueExpression, $compiler->compile($tag->defaultValue));
        }

        return $parameters;
    }

    private function addAnnotatedType(PhpDocTypeReflector $typeReflector, TypeData $type, ?TypeNode $node): TypeData
    {
        if ($node === null) {
            return $type;
        }

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
    private function reflectThrowsType(PhpDocTypeReflector $typeReflector, array $throwsTypes): ?Type
    {
        if ($throwsTypes === []) {
            return null;
        }

        return types::union(...array_map($typeReflector->reflectType(...), $throwsTypes));
    }

    private function reflectLocation(string $code, Node $node): Location
    {
        return new Location(
            startPosition: $startPosition = PhpDocParser::startPosition($node),
            endPosition: $endPosition = PhpDocParser::endPosition($node),
            startLine: PhpDocParser::startLine($node),
            endLine: PhpDocParser::endLine($node),
            startColumn: column($code, $startPosition),
            endColumn: column($code, $endPosition),
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
}
