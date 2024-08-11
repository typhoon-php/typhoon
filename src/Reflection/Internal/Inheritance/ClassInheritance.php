<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Inheritance;

use Typhoon\ChangeDetector\ChangeDetector;
use Typhoon\ChangeDetector\ChangeDetectors;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\Internal\ConstantExpression\CompilationContext;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Data\ClassKind;
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;
use Typhoon\Type\Visitor\RelativeClassTypeResolver;
use Typhoon\Type\Visitor\TemplateTypeResolver;
use Typhoon\Type\Visitor\TypeResolvers;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal\Inheritance
 */
final class ClassInheritance
{
    /**
     * @var array<non-empty-string, PropertyInheritance>
     */
    private array $constants = [];

    /**
     * @var array<non-empty-string, PropertyInheritance>
     */
    private array $properties = [];

    /**
     * @var array<non-empty-string, MethodInheritance>
     */
    private array $methods = [];

    /**
     * @var non-empty-list<ChangeDetector>
     */
    private array $changeDetectors;

    /**
     * @var array<class-string, list<Type>>
     */
    private array $ownInterfaces = [];

    /**
     * @var array<class-string, list<Type>>
     */
    private array $inheritedInterfaces = [];

    /**
     * @var array<class-string, list<Type>>
     */
    private array $parents = [];

    private function __construct(
        private readonly NamedClassId|AnonymousClassId $id,
        private readonly TypedMap $data,
        private readonly TyphoonReflector $reflector,
    ) {
        $this->changeDetectors = [$data[Data::ChangeDetector]];
    }

    public static function resolve(NamedClassId|AnonymousClassId $id, TypedMap $data, TyphoonReflector $reflector): TypedMap
    {
        $resolver = new self($id, $data, $reflector);
        $resolver->applyOwn();
        $resolver->applyUsed();
        $resolver->applyInherited();

        return $resolver->build();
    }

    private function applyOwn(): void
    {
        foreach ($this->data[Data::Constants] as $name => $constant) {
            $this->constant($name)->applyOwn($constant->with(Data::DeclaringClassId, $this->id));
        }

        foreach ($this->data[Data::Properties] as $name => $property) {
            $this->property($name)->applyOwn($property->with(Data::DeclaringClassId, $this->id));
        }

        foreach ($this->data[Data::Methods] as $name => $method) {
            $this->method($name)->applyOwn($method->with(Data::DeclaringClassId, $this->id));
        }
    }

    private function applyUsed(): void
    {
        foreach ($this->data[Data::UnresolvedTraits] as $traitName => $typeArguments) {
            $this->applyOneUsed($traitName, $typeArguments);
        }
    }

    /**
     * @param non-empty-string $traitName
     * @param list<Type> $typeArguments
     */
    private function applyOneUsed(string $traitName, array $typeArguments): void
    {
        $traitId = Id::namedClass($traitName);
        $traitData = $this->recompileTraitExpressions($this->reflector->reflect($traitId)->data);

        $this->changeDetectors[] = $traitData[Data::ChangeDetector];
        $typeResolver = $this->createTypeResolvers($traitId, $traitData, $typeArguments);

        foreach ($traitData[Data::Constants] as $constantName => $constant) {
            $this->constant($constantName)->applyUsed($constant, $typeResolver);
        }

        foreach ($traitData[Data::Properties] as $propertyName => $property) {
            $this->property($propertyName)->applyUsed($property, $typeResolver);
        }

        foreach ($traitData[Data::Methods] as $methodName => $method) {
            $precedence = $this->data[Data::TraitMethodPrecedence][$methodName] ?? null;

            if ($precedence !== null && $precedence !== $traitName) {
                continue;
            }

            foreach ($this->data[Data::TraitMethodAliases] as $alias) {
                if ($alias->trait !== $traitName || $alias->method !== $methodName) {
                    continue;
                }

                $methodToUse = $method;

                if ($alias->newVisibility !== null) {
                    $methodToUse = $methodToUse->with(Data::Visibility, $alias->newVisibility);
                }

                $this->method($alias->newName ?? $methodName)->applyUsed($methodToUse, $typeResolver);
            }

            $this->method($methodName)->applyUsed($method, $typeResolver);
        }
    }

    private function applyInherited(): void
    {
        $parent = $this->data[Data::UnresolvedParent];

        if ($parent !== null) {
            $this->addInherited(...$parent);
        }

        foreach ($this->data[Data::UnresolvedInterfaces] as $interface => $typeArguments) {
            $this->addInherited($interface, $typeArguments);
        }
    }

    /**
     * @param non-empty-string $className
     * @param list<Type> $typeArguments
     */
    private function addInherited(string $className, array $typeArguments): void
    {
        $classId = Id::namedClass($className);
        $classData = $this->reflector->reflect($classId)->data;

        $this->changeDetectors[] = $classData[Data::ChangeDetector];

        $this->inheritedInterfaces = [
            ...$this->inheritedInterfaces,
            ...$classData[Data::Interfaces],
        ];

        /** @var class-string $className */
        if ($classData[Data::ClassKind] === ClassKind::Interface) {
            $this->ownInterfaces[$className] ??= $typeArguments;
        } else {
            $this->parents = [$className => $typeArguments, ...$classData[Data::Parents]];
        }

        $typeResolver = $this->createTypeResolvers($classId, $classData, $typeArguments);

        foreach ($classData[Data::Constants] as $constantName => $constant) {
            $this->constant($constantName)->applyInherited($constant, $typeResolver);
        }

        foreach ($classData[Data::Properties] as $propertyName => $property) {
            $this->property($propertyName)->applyInherited($property, $typeResolver);
        }

        foreach ($classData[Data::Methods] as $methodName => $method) {
            $this->method($methodName)->applyInherited($method, $typeResolver);
        }
    }

    private function build(): TypedMap
    {
        return $this
            ->data
            ->with(Data::ChangeDetector, ChangeDetectors::from($this->changeDetectors))
            ->with(Data::Parents, $this->parents)
            ->with(Data::Interfaces, [...$this->ownInterfaces, ...$this->inheritedInterfaces])
            ->with(Data::Constants, array_filter(array_map(
                static fn(PropertyInheritance $resolver): ?TypedMap => $resolver->build(),
                $this->constants,
            )))
            ->with(Data::Properties, array_filter(array_map(
                static fn(PropertyInheritance $resolver): ?TypedMap => $resolver->build(),
                $this->properties,
            )))
            ->with(Data::Methods, array_filter(array_map(
                static fn(MethodInheritance $resolver): ?TypedMap => $resolver->build(),
                $this->methods,
            )))
            ->without(
                Data::UnresolvedInterfaces,
                Data::UnresolvedParent,
                Data::UnresolvedTraits,
                Data::TraitMethodAliases,
                Data::TraitMethodPrecedence,
            );
    }

    /**
     * @param non-empty-string $name
     */
    private function constant(string $name): PropertyInheritance
    {
        return $this->constants[$name] ??= new PropertyInheritance();
    }

    /**
     * @param non-empty-string $name
     */
    private function property(string $name): PropertyInheritance
    {
        return $this->properties[$name] ??= new PropertyInheritance();
    }

    /**
     * @param non-empty-string $name
     */
    private function method(string $name): MethodInheritance
    {
        return $this->methods[$name] ??= new MethodInheritance();
    }

    private function recompileTraitExpressions(TypedMap $data): TypedMap
    {
        $context = new CompilationContext($this->data[Data::Context]);

        return $data
            ->with(Data::Constants, array_map(
                static fn(TypedMap $constant): TypedMap => $constant->with(
                    Data::ValueExpression,
                    $constant[Data::ValueExpression]->recompile($context),
                ),
                $data[Data::Constants],
            ))
            ->with(Data::Properties, array_map(
                static fn(TypedMap $property): TypedMap => $property->with(
                    Data::DefaultValueExpression,
                    $property[Data::DefaultValueExpression]?->recompile($context),
                ),
                $data[Data::Properties],
            ))
            ->with(Data::Methods, array_map(
                static fn(TypedMap $method): TypedMap => $method->with(
                    Data::Parameters,
                    array_map(
                        static fn(TypedMap $parameter): TypedMap => $parameter->with(
                            Data::DefaultValueExpression,
                            $parameter[Data::DefaultValueExpression]?->recompile($context),
                        ),
                        $method[Data::Parameters],
                    ),
                ),
                $data[Data::Methods],
            ));
    }

    /**
     * @param list<Type> $typeArguments
     * @return TypeVisitor<Type>
     */
    private function createTypeResolvers(NamedClassId $inheritedId, TypedMap $inheritedData, array $typeArguments): TypeVisitor
    {
        $typeResolvers = [];

        if ($this->data[Data::ClassKind] !== ClassKind::Trait) {
            $parent = $this->data[Data::UnresolvedParent];
            $typeResolvers[] = new RelativeClassTypeResolver(
                self: $this->id,
                parent: $parent === null ? null : Id::namedClass($parent[0]),
            );
        }

        $templates = $inheritedData[Data::Templates];

        if ($templates !== []) {
            $typeResolvers[] = new TemplateTypeResolver(array_map(
                static fn(int $index, string $name, TypedMap $template): array => [
                    Id::template($inheritedId, $name),
                    $typeArguments[$index] ?? $template[Data::Constraint],
                ],
                range(0, \count($templates) - 1),
                array_keys($templates),
                $templates,
            ));
        }

        return new TypeResolvers($typeResolvers);
    }
}
