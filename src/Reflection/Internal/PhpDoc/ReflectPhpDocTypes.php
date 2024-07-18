<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpDoc;

use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassLike;
use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\PhpDoc\TypeAliasImportTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\ClassKind;
use Typhoon\Reflection\Internal\Data\Data;
use Typhoon\Reflection\Internal\Data\TypeData;
use Typhoon\Reflection\Internal\ReflectionHook\ClassReflectionHook;
use Typhoon\Reflection\Internal\ReflectionHook\FunctionReflectionHook;
use Typhoon\Reflection\Internal\Reflector;
use Typhoon\Reflection\Internal\TypeContext\AnnotatedTypesDriver;
use Typhoon\Reflection\Internal\TypeContext\TypeDeclarations;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;
use Typhoon\Type\types;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class ReflectPhpDocTypes implements AnnotatedTypesDriver, ClassReflectionHook, FunctionReflectionHook
{
    public function __construct(
        private readonly PhpDocParser $parser = new PhpDocParser(),
    ) {}

    public function reflectTypeDeclarations(FunctionLike|ClassLike $node): TypeDeclarations
    {
        $phpDoc = $this->parsePhpDoc($node->getDocComment()?->getText());

        if ($phpDoc === null) {
            return new TypeDeclarations();
        }

        return new TypeDeclarations(
            templateNames: array_column($phpDoc->templates(), 'name'),
            aliasNames: [
                ...array_column($phpDoc->typeAliases(), 'alias'),
                ...array_map(
                    static fn(TypeAliasImportTagValueNode $node): string => $node->importedAs ?? $node->importedAlias,
                    $phpDoc->typeAliasImports(),
                ),
            ],
        );
    }

    public function process(NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $id, TypedMap $data, Reflector $reflector): TypedMap
    {
        if ($id instanceof NamedFunctionId || $id instanceof AnonymousFunctionId) {
            return $this->reflectFunction($data);
        }

        return $this->reflectClass($data);
    }

    private function reflectClass(TypedMap $data): TypedMap
    {
        $typeReflector = new ContextualPhpDocTypeReflector($data[Data::TypeContext]);
        $phpDoc = $this->parsePhpDoc($data[Data::PhpDoc]);

        if ($phpDoc !== null) {
            if ($phpDoc->hasFinal()) {
                $data = $data->with(Data::AnnotatedFinal, true);
            }

            if ($phpDoc->hasReadonly()) {
                $data = $data->with(Data::AnnotatedReadonly, true);
            }

            $data = $data->with(Data::Templates, $this->reflectTemplates($typeReflector, $phpDoc, $data[Data::PhpDocStartLine]));
            $data = $data->with(Data::Aliases, $this->reflectAliases($typeReflector, $phpDoc, $data[Data::PhpDocStartLine]));
            $data = $this->reflectParent($typeReflector, $data, $phpDoc);
            $data = $this->reflectInterfaces($typeReflector, $data, $phpDoc);
            $data = $this->reflectUses($typeReflector, $data);
        }

        return $data
            ->withModifiedIfSet(Data::ClassConsts, fn(array $consts): array => array_map(
                fn(TypedMap $const): TypedMap => $this->reflectConst($typeReflector, $const),
                $consts,
            ))
            ->withModifiedIfSet(Data::Properties, fn(array $properties): array => array_map(
                fn(TypedMap $property): TypedMap => $this->reflectProperty($typeReflector, $property),
                $properties,
            ))
            ->withModifiedIfSet(Data::Methods, function (array $methods) use ($typeReflector): array {
                $methods = array_map($this->reflectFunction(...), $methods);

                if (isset($methods['__construct'])) {
                    $methods['__construct'] = $this->reflectPromotedProperties($typeReflector, $methods['__construct']);
                }

                return $methods;
            });
    }

    private function reflectParent(ContextualPhpDocTypeReflector $typeReflector, TypedMap $data, PhpDoc $phpDoc): TypedMap
    {
        $parent = $data[Data::UnresolvedParent];

        if ($parent === null) {
            return $data;
        }

        foreach ($phpDoc->extendedTypes() as $type) {
            if ($parent[0] === $typeReflector->resolveClass($type->type)) {
                $parent[1] = array_map($typeReflector->reflectType(...), $type->genericTypes);
            }
        }

        return $data->with(Data::UnresolvedParent, $parent);
    }

    private function reflectInterfaces(ContextualPhpDocTypeReflector $typeReflector, TypedMap $data, PhpDoc $phpDoc): TypedMap
    {
        $interfaces = $data[Data::UnresolvedInterfaces];

        if ($interfaces === []) {
            return $data;
        }

        $types = $data[Data::ClassKind] === ClassKind::Interface ? $phpDoc->extendedTypes() : $phpDoc->implementedTypes();

        foreach ($types as $type) {
            $name = $typeReflector->resolveClass($type->type);

            if (isset($interfaces[$name])) {
                $interfaces[$name] = array_map($typeReflector->reflectType(...), $type->genericTypes);
            }
        }

        return $data->with(Data::UnresolvedInterfaces, $interfaces);
    }

    private function reflectUses(ContextualPhpDocTypeReflector $typeReflector, TypedMap $data): TypedMap
    {
        $uses = $data[Data::UnresolvedTraits];

        if ($uses === []) {
            return $data;
        }

        foreach ($data[Data::UsePhpDocs] as $phpDocText) {
            $phpDoc = $this->parsePhpDoc($phpDocText);

            foreach ($phpDoc->usedTypes() as $type) {
                $name = $typeReflector->resolveClass($type->type);

                if (isset($uses[$name])) {
                    $uses[$name] = array_map($typeReflector->reflectType(...), $type->genericTypes);
                }
            }
        }

        return $data->with(Data::UnresolvedTraits, $uses);
    }

    /**
     * @param ?positive-int $phpDocStartLine
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectAliases(ContextualPhpDocTypeReflector $typeReflector, PhpDoc $phpDoc, ?int $phpDocStartLine): array
    {
        $aliases = [];

        foreach ($phpDoc->typeAliases() as $alias) {
            $data = (new TypedMap())->with(Data::AliasType, $typeReflector->reflectType($alias->type));
            $aliases[$alias->alias] = $this->setLines($data, $alias, $phpDocStartLine);
        }

        foreach ($phpDoc->typeAliasImports() as $aliasImport) {
            $data = (new TypedMap())->with(
                Data::AliasType,
                types::alias(Id::alias($typeReflector->resolveClass($aliasImport->importedFrom), $aliasImport->importedAlias)),
            );
            $aliases[$aliasImport->importedAs ?? $aliasImport->importedAlias] = $this->setLines($data, $aliasImport, $phpDocStartLine);
        }

        return $aliases;
    }

    /**
     * @param ?positive-int $phpDocStartLine
     * @return array<non-empty-string, TypedMap>
     */
    private function reflectTemplates(ContextualPhpDocTypeReflector $typeReflector, PhpDoc $phpDoc, ?int $phpDocStartLine): array
    {
        $templates = [];

        foreach ($phpDoc->templates() as $index => $template) {
            $data = (new TypedMap())
                ->with(Data::Index, $index)
                ->with(Data::Constraint, $typeReflector->reflectType($template->bound) ?? types::mixed)
                ->with(Data::Variance, PhpDoc::templateTagVariance($template));
            $templates[$template->name] = $this->setLines($data, $template, $phpDocStartLine);
        }

        return $templates;
    }

    private function reflectFunction(TypedMap $data): TypedMap
    {
        $phpDoc = $this->parsePhpDoc($data[Data::PhpDoc]);

        if ($phpDoc === null) {
            return $data;
        }

        $typeReflector = new ContextualPhpDocTypeReflector($data[Data::TypeContext]);

        $data = $data
            ->with(Data::Templates, $this->reflectTemplates($typeReflector, $phpDoc, $data[Data::PhpDocStartLine]))
            ->withModifiedIfSet(Data::Parameters, function (array $parameters) use ($typeReflector, $phpDoc): array {
                $types = $phpDoc->paramTypes();

                foreach ($types as $name => $type) {
                    if (isset($parameters[$name])) {
                        $parameters[$name] = $this->setAnnotatedType($typeReflector, $type, $parameters[$name]);
                    }
                }

                return $parameters;
            });

        $returnType = $phpDoc->returnType();

        if ($returnType !== null) {
            $data = $this->setAnnotatedType($typeReflector, $returnType, $data);
        }

        $throwsTypes = $phpDoc->throwsTypes();

        if ($throwsTypes !== []) {
            $data = $data->with(Data::ThrowsType, types::union(...array_map($typeReflector->reflectType(...), $throwsTypes)));
        }

        return $data;
    }

    private function reflectConst(ContextualPhpDocTypeReflector $typeReflector, TypedMap $data): TypedMap
    {
        $phpDoc = $this->parsePhpDoc($data[Data::PhpDoc]);

        if ($phpDoc === null) {
            return $data;
        }

        if ($phpDoc->hasFinal()) {
            $data = $data->with(Data::AnnotatedFinal, true);
        }

        $type = $phpDoc->varType();

        if ($type !== null) {
            $data = $this->setAnnotatedType($typeReflector, $type, $data);
        }

        return $data;
    }

    private function reflectProperty(ContextualPhpDocTypeReflector $typeReflector, TypedMap $data): TypedMap
    {
        $phpDoc = $this->parsePhpDoc($data[Data::PhpDoc]);

        if ($phpDoc === null) {
            return $data;
        }

        if ($phpDoc->hasReadonly()) {
            $data = $data->with(Data::AnnotatedReadonly, true);
        }

        $type = $phpDoc->varType();

        if ($type !== null) {
            $data = $this->setAnnotatedType($typeReflector, $type, $data);
        }

        return $data;
    }

    private function reflectPromotedProperties(ContextualPhpDocTypeReflector $typeReflector, TypedMap $data): TypedMap
    {
        return $data->withModifiedIfSet(Data::Parameters, fn(array $parameters): array => array_map(
            fn(TypedMap $parameter): TypedMap => $parameter[Data::Promoted] ? $this->reflectProperty($typeReflector, $parameter) : $parameter,
            $parameters,
        ));
    }

    private function setAnnotatedType(ContextualPhpDocTypeReflector $typeReflector, TypeNode $node, TypedMap $data): TypedMap
    {
        return $data->withModified(
            Data::Type,
            static fn(TypeData $type): TypeData => $type->withAnnotated($typeReflector->reflectType($node)),
        );
    }

    /**
     * @param ?positive-int $phpDocStartLine
     */
    private function setLines(TypedMap $data, Node $node, ?int $phpDocStartLine): TypedMap
    {
        if ($phpDocStartLine === null) {
            return $data;
        }

        $startLine = $node->getAttribute('startLine');
        $endLine = $node->getAttribute('endLine');

        if (\is_int($startLine) && $startLine > 0 && \is_int($endLine) && $endLine > 0) {
            $data = $data
                ->with(Data::StartLine, $phpDocStartLine - 1 + $startLine)
                ->with(Data::EndLine, $phpDocStartLine - 1 + $endLine);
        }

        return $data;
    }

    /**
     * @return ($text is null ? null : PhpDoc)
     */
    private function parsePhpDoc(?string $text): ?PhpDoc
    {
        if ($text === null || $text === '') {
            return null;
        }

        return $this->parser->parse($text);
    }
}
