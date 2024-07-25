<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

/**
 * @api
 * @psalm-type Attributes = Collection<non-negative-int, AttributeReflection>
 * @psalm-type Aliases = Collection<non-empty-string, AliasReflection>
 * @psalm-type Templates = Collection<non-empty-string, TemplateReflection>
 * @psalm-type ClassConstants = Collection<non-empty-string, ClassConstantReflection>
 * @psalm-type Methods = Collection<non-empty-string, MethodReflection>
 * @psalm-type Properties = Collection<non-empty-string, PropertyReflection>
 * @psalm-type Parameters = Collection<non-empty-string, ParameterReflection>
 */
enum ReflectionCollections {}
