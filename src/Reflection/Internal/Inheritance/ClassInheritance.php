<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Inheritance;

use Typhoon\ChangeDetector\ChangeDetector;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\Id;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\Reflection\ClassKind;
use Typhoon\Reflection\Internal\Data\Data;
use Typhoon\Reflection\Internal\Reflector;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;
use Typhoon\Type\Type;
use Typhoon\Type\Visitor\RelativeClassTypeResolver;
use Typhoon\Type\Visitor\TemplateTypeResolver;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal\Inheritance
 */
final class ClassInheritance
{
    /**
     * @var array<non-empty-string, PropertyInheritance>
     */
    private array $consts = [];

    /**
     * @var array<non-empty-string, PropertyInheritance>
     */
    private array $properties = [];

    /**
     * @var array<non-empty-string, MethodInheritance>
     */
    private array $methods = [];

    /**
     * @var list<ChangeDetector>
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
        private readonly Reflector $reflector,
        private readonly NamedClassId|AnonymousClassId $id,
        private readonly TypedMap $data,
    ) {
        $this->changeDetectors = $data[Data::UnresolvedChangeDetectors];
    }

    public static function resolve(Reflector $reflector, NamedClassId|AnonymousClassId $id, TypedMap $data): TypedMap
    {
        $resolver = new self($reflector, $id, $data);
        $resolver->applyOwn();
        $resolver->applyUsed();
        $resolver->applyInherited();

        return $resolver->build();
    }

    private function applyOwn(): void
    {
        foreach ($this->data[Data::ClassConsts] as $name => $constant) {
            $this->const($name)->applyOwn($constant->with(Data::DeclaringClassId, $this->id));
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
        foreach ($this->data[Data::UnresolvedTraits] as $traitName => $arguments) {
            $this->applyOneUsed($traitName, $arguments);
        }
    }

    /**
     * @param non-empty-string $traitName
     * @param list<Type> $arguments
     */
    private function applyOneUsed(string $traitName, array $arguments): void
    {
        $traitId = Id::namedClass($traitName);
        $traitData = $this->reflector->reflect($traitId);

        $this->changeDetectors[] = $traitData[Data::ChangeDetector];

        $typeResolvers = $this->buildTypeResolvers($traitId, $traitData, $arguments);

        foreach ($traitData[Data::ClassConsts] as $constantName => $constant) {
            $this->const($constantName)->applyUsed($constant, $typeResolvers);
        }

        foreach ($traitData[Data::Properties] as $propertyName => $property) {
            $this->property($propertyName)->applyUsed($property, $typeResolvers);
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

                $this->method($alias->newName ?? $methodName)->applyUsed($methodToUse, $typeResolvers);
            }

            $this->method($methodName)->applyUsed($method, $typeResolvers);
        }
    }

    private function applyInherited(): void
    {
        $parent = $this->data[Data::UnresolvedParent];

        if ($parent !== null) {
            $this->addInherited(...$parent);
        }

        foreach ($this->data[Data::UnresolvedInterfaces] as $interface => $arguments) {
            $this->addInherited($interface, $arguments);
        }
    }

    /**
     * @param non-empty-string $className
     * @param list<Type> $arguments
     */
    private function addInherited(string $className, array $arguments): void
    {
        $classId = Id::namedClass($className);
        $classData = $this->reflector->reflect($classId);

        $this->changeDetectors[] = $classData[Data::ChangeDetector];

        $this->inheritedInterfaces = [
            ...$this->inheritedInterfaces,
            ...$classData[Data::Interfaces],
        ];

        /** @var class-string $className */
        if ($classData[Data::ClassKind] === ClassKind::Interface) {
            $this->ownInterfaces[$className] ??= $arguments;
        } else {
            $this->parents = [$className => $arguments, ...$classData[Data::Parents]];
        }

        $typeResolvers = $this->buildTypeResolvers($classId, $classData, $arguments);

        foreach ($classData[Data::ClassConsts] as $constantName => $constant) {
            $this->const($constantName)->applyInherited($constant, $typeResolvers);
        }

        foreach ($classData[Data::Properties] as $propertyName => $property) {
            $this->property($propertyName)->applyInherited($property, $typeResolvers);
        }

        foreach ($classData[Data::Methods] as $methodName => $method) {
            $this->method($methodName)->applyInherited($method, $typeResolvers);
        }
    }

    private function build(): TypedMap
    {
        return $this
            ->data
            ->with(Data::UnresolvedChangeDetectors, $this->changeDetectors)
            ->with(Data::Parents, $this->parents)
            ->with(Data::Interfaces, [...$this->ownInterfaces, ...$this->inheritedInterfaces])
            ->with(Data::ClassConsts, array_filter(array_map(
                static fn(PropertyInheritance $resolver): ?TypedMap => $resolver->build(),
                $this->consts,
            )))
            ->with(Data::Properties, array_filter(array_map(
                static fn(PropertyInheritance $resolver): ?TypedMap => $resolver->build(),
                $this->properties,
            )))
            ->with(Data::Methods, array_filter(array_map(
                static fn(MethodInheritance $resolver): ?TypedMap => $resolver->build(),
                $this->methods,
            )));
    }

    /**
     * @param list<Type> $typeArguments
     */
    private function buildTypeResolvers(NamedClassId $id, TypedMap $inheritedData, array $typeArguments): TypeResolvers
    {
        $resolvers = [];
        $templates = $inheritedData[Data::Templates];

        if ($templates !== []) {
            $resolvers[] = new TemplateTypeResolver(array_map(
                static fn(string $name, TypedMap $template): array => [
                    Id::template($id, $name),
                    $typeArguments[$template[Data::Index]] ?? $template[Data::Constraint],
                ],
                array_keys($templates),
                $templates,
            ));
        }

        if ($this->data[Data::ClassKind] !== ClassKind::Trait) {
            $parent = $this->data[Data::UnresolvedParent];
            $resolvers[] = new RelativeClassTypeResolver(
                self: $this->id,
                parent: $parent === null ? null : Id::namedClass($parent[0]),
            );
        }

        return new TypeResolvers($resolvers);
    }

    /**
     * @param non-empty-string $name
     */
    private function const(string $name): PropertyInheritance
    {
        return $this->consts[$name] ??= new PropertyInheritance();
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
}
