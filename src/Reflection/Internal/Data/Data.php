<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

/**
 * @internal
 * @psalm-internal Typhoon
 */
enum Data
{
    public const ArgumentExpressions = ArgumentExpressionsKey::Key;
    public const AttributeClassName = AttributeClassNameKey::Key;
    public const AttributeRepeated = BoolKeys::AttributeRepeated;
    public const Attributes = AttributesKey::Key;
    public const ClassConsts = NamedDataKeys::ClassConsts;
    public const ClassKind = ClassKindKey::Key;
    public const Namespace = NamespaceKey::Key;
    public const DeclaringClassId = DeclaringClassIdKey::Key;
    public const DefaultValueExpression = DefaultValueExpressionKey::Key;
    public const PhpDocStartLine = LineKeys::PhpDocStart;
    public const StartLine = LineKeys::Start;
    public const EndLine = LineKeys::End;
    public const EnumCase = BoolKeys::EnumCase;
    public const EnumBackingValueExpression = EnumBackingValueExpressionKey::Key;
    public const EnumBackingType = EnumBackingTypeKey::Key;
    public const File = FileKey::Key;
    public const Index = ParameterIndexKey::Key;
    public const Abstract = BoolKeys::IsAbstract;
    public const AnnotatedFinal = BoolKeys::AnnotatedFinal;
    public const AnnotatedReadonly = BoolKeys::AnnotatedReadonly;
    public const ByReference = BoolKeys::ByReference;
    public const Generator = BoolKeys::Generator;
    public const NativeFinal = BoolKeys::NativeFinal;
    public const NativeReadonly = BoolKeys::NativeReadonly;
    public const Promoted = BoolKeys::Promoted;
    public const Static = BoolKeys::IsStatic;
    public const Variadic = BoolKeys::Variadic;
    public const InternallyDefined = BoolKeys::InternallyDefined;
    public const Methods = NamedDataKeys::Methods;
    public const Parameters = NamedDataKeys::Parameters;
    public const PhpDoc = PhpDocKey::Key;
    public const PhpExtension = PhpExtensionKey::Key;
    public const Templates = NamedDataKeys::Templates;
    public const Aliases = NamedDataKeys::Aliases;
    public const Properties = NamedDataKeys::Properties;
    public const ChangeDetector = ChangeDetectorKey::Key;
    public const Interfaces = InterfacesKey::Key;
    public const Parents = ParentsKey::Key;
    public const ThrowsType = ThrowsTypeKey::Key;
    public const AliasType = AliasTypeKey::Key;
    public const TraitMethodAliases = TraitMethodAliasesKey::Key;
    public const TraitMethodPrecedence = TraitMethodPrecedenceKey::Key;
    public const TypeContext = TypeContextKey::Key;
    public const UnresolvedChangeDetectors = UnresolvedChangeDetectorsKey::Key;
    public const UnresolvedInterfaces = UnresolvedInterfacesKey::Key;
    public const UnresolvedParent = UnresolvedParentKey::Key;
    public const UnresolvedTraits = UnresolvedTraitsKey::Key;
    public const ValueExpression = ValueExpressionKey::Key;
    public const Visibility = VisibilityKey::Key;
    public const Type = TypeDataKeys::Type;
    public const Variance = VarianceKey::Key;
    public const Constraint = ConstraintKey::Key;
    public const UsePhpDocs = UsePhpDocsKey::Key;
    public const AnonymousClassColumns = AnonymousClassColumnsKey::Key;
}
