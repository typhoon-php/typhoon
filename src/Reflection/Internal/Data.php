<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

/**
 * @internal
 * @psalm-internal Typhoon
 */
enum Data
{
    public const ArgumentsExpression = Data\ArgumentsExpressionKey::Key;
    public const AttributeClassName = Data\AttributeClassNameKey::Key;
    public const AttributeRepeated = Data\BoolKeys::AttributeRepeated;
    public const Attributes = Data\AttributesKey::Key;
    public const Constants = Data\NamedDataKeys::Constants;
    public const ClassKind = Data\ClassKindKey::Key;
    public const Namespace = Data\NamespaceKey::Key;
    public const DeclaringClassId = Data\DeclaringClassIdKey::Key;
    public const DefaultValueExpression = Data\DefaultValueExpressionKey::Key;
    public const EnumCase = Data\BoolKeys::EnumCase;
    public const BackingValueExpression = Data\BackingValueExpressionKey::Key;
    public const BackingType = Data\BackingTypeKey::Key;
    public const File = Data\FileKey::Key;
    public const Index = Data\ParameterIndexKey::Key;
    public const Abstract = Data\BoolKeys::IsAbstract;
    public const AnnotatedFinal = Data\BoolKeys::AnnotatedFinal;
    public const AnnotatedReadonly = Data\BoolKeys::AnnotatedReadonly;
    public const ByReference = Data\BoolKeys::ByReference;
    public const Generator = Data\BoolKeys::Generator;
    public const NativeFinal = Data\BoolKeys::NativeFinal;
    public const NativeReadonly = Data\BoolKeys::NativeReadonly;
    public const Promoted = Data\BoolKeys::Promoted;
    public const Static = Data\BoolKeys::IsStatic;
    public const Variadic = Data\BoolKeys::Variadic;
    public const InternallyDefined = Data\BoolKeys::InternallyDefined;
    public const Annotated = Data\BoolKeys::Annotated;
    public const Methods = Data\NamedDataKeys::Methods;
    public const Parameters = Data\NamedDataKeys::Parameters;
    public const PhpDoc = Data\PhpDocKey::Key;
    public const PhpExtension = Data\PhpExtensionKey::Key;
    public const Templates = Data\NamedDataKeys::Templates;
    public const Aliases = Data\NamedDataKeys::Aliases;
    public const Properties = Data\NamedDataKeys::Properties;
    public const ChangeDetector = Data\ChangeDetectorKey::Key;
    public const Interfaces = Data\InterfacesKey::Key;
    public const Parents = Data\ParentsKey::Key;
    public const ThrowsType = Data\ThrowsTypeKey::Key;
    public const AliasType = Data\AliasTypeKey::Key;
    public const TraitMethodAliases = Data\TraitMethodAliasesKey::Key;
    public const TraitMethodPrecedence = Data\TraitMethodPrecedenceKey::Key;
    public const Context = Data\ContextKey::Key;
    public const UnresolvedInterfaces = Data\UnresolvedInterfacesKey::Key;
    public const UnresolvedParent = Data\UnresolvedParentKey::Key;
    public const UnresolvedTraits = Data\UnresolvedTraitsKey::Key;
    public const ValueExpression = Data\ValueExpressionKey::Key;
    public const Visibility = Data\VisibilityKey::Key;
    public const Type = Data\TypeDataKeys::Type;
    public const Variance = Data\VarianceKey::Key;
    public const Constraint = Data\ConstraintKey::Key;
    public const UsePhpDocs = Data\UsePhpDocsKey::Key;
    public const AnonymousClassColumns = Data\AnonymousClassColumnsKey::Key;
    public const Location = Data\LocationKey::Key;
    public const Code = Data\CodeKey::Key;
    public const Deprecation = Data\DeprecationKey::Key;
}
