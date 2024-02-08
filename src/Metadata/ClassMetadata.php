<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Metadata;

use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\TemplateReflection;
use Typhoon\Type\NamedObjectType;
use Typhoon\Type\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @template-covariant T of object
 * @template-extends RootMetadata<class-string<T>>
 * @psalm-suppress PossiblyUnusedProperty
 */
final class ClassMetadata extends RootMetadata
{
    /**
     * @param class-string<T> $name
     * @param non-empty-string|false $extension
     * @param non-empty-string|false $file
     * @param positive-int|false $startLine
     * @param positive-int|false $endLine
     * @param non-empty-string|false $docComment
     * @param list<AttributeMetadata> $attributes
     * @param array<non-empty-string, Type> $typeAliases
     * @param list<TemplateReflection> $templates
     * @param int-mask-of<ClassReflection::IS_*> $modifiers
     * @param list<NamedObjectType> $ownInterfaceTypes
     * @param list<NamedObjectType> $ownTraitTypes
     * @param list<PropertyMetadata> $ownProperties
     * @param list<MethodMetadata> $ownMethods
     */
    public function __construct(
        ChangeDetector $changeDetector,
        string $name,
        public readonly bool $internal,
        public readonly string|false $extension,
        public readonly string|false $file,
        public readonly int|false $startLine,
        public readonly int|false $endLine,
        public readonly string|false $docComment,
        public readonly array $attributes,
        public readonly array $typeAliases,
        public readonly array $templates,
        public readonly bool $interface,
        public readonly bool $enum,
        public readonly bool $trait,
        public readonly int $modifiers,
        public readonly bool $anonymous,
        public readonly bool $deprecated,
        public readonly ?NamedObjectType $parentType,
        public readonly array $ownInterfaceTypes,
        public readonly array $ownProperties,
        public readonly array $ownMethods,
        public readonly array $ownTraitTypes = [],
    ) {
        parent::__construct($name, $changeDetector);
    }
}
