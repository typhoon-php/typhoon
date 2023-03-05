<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\TypeReflector;

use ExtendedTypeSystem\ClassLikeTypeReflection;
use ExtendedTypeSystem\MethodTypeReflection;
use ExtendedTypeSystem\Type;
use ExtendedTypeSystem\Type\NamedObjectType;
use ExtendedTypeSystem\TypeReflector;
use ExtendedTypeSystem\types;
use PhpParser\NameContext;
use PhpParser\Node\Name;
use PHPStan\PhpDocParser\Ast\PhpDoc\ExtendsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ImplementsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;

/**
 * @internal
 * @psalm-internal ExtendedTypeSystem\TypeReflector
 */
final class ClassLikeContext extends Context
{
    /**
     * @var list<array{non-empty-list<class-string>, list<PhpDocTagNode>}>
     */
    private array $traits = [];

    /**
     * @var array<non-empty-string, Type>
     */
    private array $propertyTypes = [];

    /**
     * @var array<non-empty-string, MethodTypeReflection>
     */
    private array $methods = [];

    /**
     * @param list<PhpDocTagNode> $phpDocTags
     * @param class-string $name
     * @param ?class-string $parent
     * @param list<class-string> $interfaces
     */
    public function __construct(
        private readonly NameContext $nameContext,
        array $phpDocTags,
        private readonly string $name,
        private readonly ?string $parent = null,
        private readonly array $interfaces = [],
    ) {
        parent::__construct($phpDocTags);
    }

    public function self(): string
    {
        return $this->name;
    }

    public function parent(): string
    {
        return $this->parent ?? throw new \LogicException('todo');
    }

    public function resolveName(Name $name): Name
    {
        return $this->nameContext->getResolvedClassName($name);
    }

    public function tryResolveTemplateType(string $name): ?Type
    {
        if ($this->hasTemplate($name)) {
            return types::classTemplate($name, $this->name);
        }

        return null;
    }

    /**
     * @param non-empty-list<class-string> $traits
     * @param list<PhpDocTagNode> $phpDocTags
     */
    public function addTraits(array $traits, array $phpDocTags): void
    {
        $this->traits[] = [$traits, $phpDocTags];
    }

    /**
     * @param non-empty-string $name
     */
    public function addPropertyType(string $name, Type $type): void
    {
        $this->propertyTypes[$name] = $type;
    }

    public function addMethodTypeReflection(MethodTypeReflection $method): void
    {
        $this->methods[$method->name] = $method;
    }

    public function build(TypeReflector $typeReflector, TypeResolver $typeResolver): ClassLikeTypeReflection
    {
        $parentTemplateArguments = null;
        $interfacesTemplateArguments = array_fill_keys($this->interfaces, []);

        foreach ($this->phpDocTags as $tag) {
            if (!$tag->value instanceof ExtendsTagValueNode && !$tag->value instanceof ImplementsTagValueNode) {
                continue;
            }

            $type = $typeResolver->resolveTypeNode($this, $tag->value->type);

            if (!$type instanceof NamedObjectType) {
                throw new \LogicException('todo');
            }

            if ($this->parent === $type->class) {
                $parentTemplateArguments = $type->templateArguments;

                continue;
            }

            if (isset($interfacesTemplateArguments[$type->class])) {
                $interfacesTemplateArguments[$type->class] = $type->templateArguments;
            }
        }

        return new ClassLikeTypeReflection(
            typeReflector: $typeReflector,
            name: $this->name,
            parentClass: $this->parent,
            templates: $this->buildTemplateReflections($typeResolver),
            parentTemplateArguments: $parentTemplateArguments,
            interfacesTemplateArguments: $interfacesTemplateArguments,
            traitsTemplateArguments: [], // todo
            propertyTypes: $this->propertyTypes,
            methods: $this->methods,
        );
    }
}
