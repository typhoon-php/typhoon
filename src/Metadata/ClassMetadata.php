<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Metadata;

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
     * @param int-mask-of<\ReflectionClass::IS_*> $modifiers
     * @param list<NamedObjectType> $ownInterfaceTypes
     * @param list<NamedObjectType> $ownTraitTypes
     * @param list<PropertyMetadata> $ownProperties
     * @param list<MethodMetadata> $ownMethods
     */
    public function __construct(
        ChangeDetector $changeDetector,
        string $name,
        public readonly int $modifiers,
        public readonly bool $internal = false,
        public readonly string|false $extension = false,
        public readonly string|false $file = false,
        public readonly int|false $startLine = false,
        public readonly int|false $endLine = false,
        public readonly string|false $docComment = false,
        public readonly array $attributes = [],
        public readonly array $typeAliases = [],
        public readonly array $templates = [],
        public readonly bool $interface = false,
        public readonly bool $enum = false,
        public readonly bool $trait = false,
        public readonly bool $anonymous = false,
        public readonly bool $deprecated = false,
        public readonly ?NamedObjectType $parentType = null,
        public readonly array $ownInterfaceTypes = [],
        public readonly array $ownProperties = [],
        public readonly array $ownMethods = [],
        public readonly array $ownTraitTypes = [],
    ) {
        parent::__construct($name, $changeDetector);
    }
}
