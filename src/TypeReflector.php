<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

use ExtendedTypeSystem\Metadata\Metadata;
use ExtendedTypeSystem\Metadata\MetadataFactory;
use ExtendedTypeSystem\NameResolution\NameResolverFactory;
use ExtendedTypeSystem\Parser\PHPDocParser;
use ExtendedTypeSystem\Type\ArrayKeyT;
use ExtendedTypeSystem\Type\ArrayShapeItem;
use ExtendedTypeSystem\Type\ArrayShapeT;
use ExtendedTypeSystem\Type\ArrayT;
use ExtendedTypeSystem\Type\BoolT;
use ExtendedTypeSystem\Type\CallableT;
use ExtendedTypeSystem\Type\FalseT;
use ExtendedTypeSystem\Type\FloatLiteralT;
use ExtendedTypeSystem\Type\FloatT;
use ExtendedTypeSystem\Type\IntersectionT;
use ExtendedTypeSystem\Type\IntLiteralT;
use ExtendedTypeSystem\Type\IntT;
use ExtendedTypeSystem\Type\IterableT;
use ExtendedTypeSystem\Type\ListT;
use ExtendedTypeSystem\Type\MixedT;
use ExtendedTypeSystem\Type\NamedObjectT;
use ExtendedTypeSystem\Type\NeverT;
use ExtendedTypeSystem\Type\NonEmptyArrayT;
use ExtendedTypeSystem\Type\NonEmptyListT;
use ExtendedTypeSystem\Type\NonEmptyStringT;
use ExtendedTypeSystem\Type\NullableT;
use ExtendedTypeSystem\Type\NullT;
use ExtendedTypeSystem\Type\NumericStringT;
use ExtendedTypeSystem\Type\NumericT;
use ExtendedTypeSystem\Type\ObjectT;
use ExtendedTypeSystem\Type\PositiveIntT;
use ExtendedTypeSystem\Type\ScalarT;
use ExtendedTypeSystem\Type\StaticT;
use ExtendedTypeSystem\Type\StringLiteralT;
use ExtendedTypeSystem\Type\StringT;
use ExtendedTypeSystem\Type\TrueT;
use ExtendedTypeSystem\Type\UnionT;
use ExtendedTypeSystem\Type\VoidT;
use phpDocumentor\Reflection\FqsenResolver;
use phpDocumentor\Reflection\Types\ContextFactory;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprFloatNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprIntegerNode;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ExtendsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ImplementsTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\TemplateTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser as PHPStanPhpDocParser;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPyh\LRUMemoizer\LRUMemoizer;

/**
 * @psalm-api
 */
final class TypeReflector
{
    private readonly MetadataFactory $metadataFactory;

    public function __construct(
        TagPrioritizer $tagPrioritizer = new PHPStanOverPsalmOverOtherTagsPrioritizer(),
        LRUMemoizer $memoizer = new LRUMemoizer(),
        PHPStanPhpDocParser $phpDocParser = new PHPStanPhpDocParser(new TypeParser(new ConstExprParser()), new ConstExprParser()),
        Lexer $phpDocLexer = new Lexer(),
        ContextFactory $contextFactory = new ContextFactory(),
        FqsenResolver $fqsenResolver = new FqsenResolver(),
    ) {
        $this->metadataFactory = new MetadataFactory(
            memoizer: $memoizer,
            phpDocParser: new PHPDocParser(
                parser: $phpDocParser,
                lexer: $phpDocLexer,
                prioritizer: $tagPrioritizer,
            ),
            nameResolverFactory: new NameResolverFactory(
                contextFactory: $contextFactory,
                fqsenResolver: $fqsenResolver,
            ),
        );
    }

    /**
     * @param non-empty-string $type
     * @param ?class-string $scopeClass
     */
    public function reflectTypeFromString(string $type, ?string $scopeClass = null): ?Type
    {
        $metadata = $this->metadataFactory->fromStringMetadata("/** @var {$type} */", $scopeClass);
        $tagValue = $metadata->phpDocTags->findTagValue(VarTagValueNode::class);

        if ($tagValue === null) {
            // todo
            return null;
        }

        return $this->reflectTypeNodeType(metadata: $metadata, typeNode: $tagValue->type);
    }

    /**
     * @param callable-string|\Closure $function
     * @param non-empty-string $parameter
     */
    public function reflectFunctionParameterType(string|\Closure $function, string $parameter): ?Type
    {
        $metadata = $this->metadataFactory->functionMetadata($function);

        foreach ($metadata->phpDocTags->findTagValues(ParamTagValueNode::class) as $tagValue) {
            if ($tagValue->parameterName === '$'.$parameter) {
                return $this->reflectTypeNodeType($metadata, $tagValue->type);
            }
        }

        return $this->reflectReflectionType($metadata, (new \ReflectionParameter($function, $parameter))->getType());
    }

    /**
     * @param callable-string|\Closure $function
     */
    public function reflectFunctionReturnType(string|\Closure $function): ?Type
    {
        $metadata = $this->metadataFactory->functionMetadata($function);
        $tagValue = $metadata->phpDocTags->findTagValue(ReturnTagValueNode::class);

        if ($tagValue !== null) {
            return $this->reflectTypeNodeType($metadata, $tagValue->type);
        }

        return $this->reflectReflectionType($metadata, (new \ReflectionFunction($function))->getReturnType());
    }

    /**
     * @param class-string $class
     * @param non-empty-string $property
     */
    public function reflectPropertyType(string $class, string $property): ?Type
    {
        $metadata = $this->metadataFactory->propertyMetadata($class, $property);
        $tagValue = $metadata->phpDocTags->findTagValue(VarTagValueNode::class);

        if ($tagValue !== null) {
            return $this->reflectTypeNodeType($metadata, $tagValue->type);
        }

        if ($metadata->promoted) {
            return $this->reflectMethodParameterType(class: $class, method: '__construct', parameter: $property);
        }

        return $this->reflectReflectionType($metadata, (new \ReflectionProperty($class, $property))->getType());
    }

    /**
     * @param class-string $class
     * @param non-empty-string $method
     * @param non-empty-string $parameter
     */
    public function reflectMethodParameterType(string $class, string $method, string $parameter): ?Type
    {
        $metadata = $this->metadataFactory->methodMetadata($class, $method);

        foreach ($metadata->phpDocTags->findTagValues(ParamTagValueNode::class) as $tagValue) {
            if ($tagValue->parameterName === '$'.$parameter) {
                return $this->reflectTypeNodeType($metadata, $tagValue->type);
            }
        }

        return $this->reflectReflectionType($metadata, (new \ReflectionParameter([$class, $method], $parameter))->getType());
    }

    /**
     * @param class-string $class
     * @param non-empty-string $method
     */
    public function reflectMethodReturnType(string $class, string $method): ?Type
    {
        $metadata = $this->metadataFactory->methodMetadata($class, $method);
        $tagValue = $metadata->phpDocTags->findTagValue(ReturnTagValueNode::class);

        if ($tagValue !== null) {
            return $this->reflectTypeNodeType($metadata, $tagValue->type);
        }

        return $this->reflectReflectionType($metadata, (new \ReflectionMethod($class, $method))->getReturnType());
    }

    /**
     * @param callable-string $function
     * @return list<Template>
     */
    public function reflectFunctionTemplates(string $function): array
    {
        return $this->reflectTemplates($this->metadataFactory->functionMetadata($function));
    }

    /**
     * @param class-string $class
     * @return list<Template>
     */
    public function reflectClassTemplates(string $class): array
    {
        return $this->reflectTemplates($this->metadataFactory->classMetadata($class));
    }

    /**
     * @param class-string $class
     */
    public function reflectClassTemplateExtends(string $class): ?NamedObjectT
    {
        $metadata = $this->metadataFactory->classMetadata($class);
        $tagValue = $metadata->phpDocTags->findTagValue(ExtendsTagValueNode::class);

        if ($tagValue === null) {
            return null;
        }

        $type = $this->reflectTypeNodeType($metadata, $tagValue->type);

        if (!$type instanceof NamedObjectT) {
            throw new \LogicException(sprintf(
                '%s of %s must have been reflected to NamedObjectT.',
                (string) $tagValue->type,
                $class,
            ));
        }

        return $type;
    }

    /**
     * @param class-string $class
     * @return list<NamedObjectT>
     */
    public function reflectClassTemplateImplements(string $class): array
    {
        $metadata = $this->metadataFactory->classMetadata($class);
        $types = [];

        foreach ($metadata->phpDocTags->findTagValues(ImplementsTagValueNode::class) as $tagValue) {
            $type = $this->reflectTypeNodeType($metadata, $tagValue->type);

            if (!$type instanceof NamedObjectT) {
                throw new \LogicException(sprintf(
                    '%s of %s must have been reflected to NamedObjectT.',
                    (string) $tagValue->type,
                    $class,
                ));
            }

            $types[] = $type;
        }

        return $types;
    }

    /**
     * @param class-string $class
     * @param non-empty-string $method
     */
    public function reflectMethodTemplates(string $class, string $method): array
    {
        return $this->reflectTemplates($this->metadataFactory->methodMetadata($class, $method));
    }

    /**
     * @return list<Template>
     */
    private function reflectTemplates(Metadata $metadata): array
    {
        $index = 0;
        $templates = [];

        foreach ($metadata->phpDocTags->findTagValues(TemplateTagValueNode::class) as $tagName => $tagValue) {
            $templates[] = new Template(
                index: $index,
                name: $tagValue->name ?: throw new \LogicException('Tag cannot have empty name.'),
                constraint: $this->reflectTypeNodeType($metadata, $tagValue->bound) ?? new MixedT(),
                variance: match (true) {
                    str_ends_with($tagName, 'covariant') => Variance::COVARIANT,
                    str_ends_with($tagName, 'contravariant') => Variance::CONTRAVARIANT,
                    default => Variance::INVARIANT,
                },
            );
            ++$index;
        }

        return $templates;
    }

    /**
     * @return ($typeNode is null ? null : Type)
     */
    private function reflectTypeNodeType(Metadata $metadata, ?TypeNode $typeNode): ?Type
    {
        if ($typeNode === null) {
            return null;
        }

        if ($typeNode instanceof NullableTypeNode) {
            return new NullableT($this->reflectTypeNodeType($metadata, $typeNode->type));
        }

        if ($typeNode instanceof ArrayTypeNode) {
            return new ArrayT(valueType: $this->reflectTypeNodeType($metadata, $typeNode->type));
        }

        if ($typeNode instanceof ArrayShapeNode) {
            return $this->reflectArrayShapeNodeType($metadata, $typeNode);
        }

        if ($typeNode instanceof UnionTypeNode) {
            if ($typeNode->types === []) {
                return new MixedT();
            }

            return new UnionT(...array_values(array_map(
                fn (TypeNode $node): Type => $this->reflectTypeNodeType($metadata, $node),
                $typeNode->types,
            )));
        }

        if ($typeNode instanceof IntersectionTypeNode) {
            if ($typeNode->types === []) {
                return new MixedT();
            }

            return new IntersectionT(...array_values(array_map(
                fn (TypeNode $node): Type => $this->reflectTypeNodeType($metadata, $node),
                $typeNode->types,
            )));
        }

        if ($typeNode instanceof ConstTypeNode) {
            $exprNode = $typeNode->constExpr;

            if ($exprNode instanceof ConstExprIntegerNode) {
                return new IntLiteralT((int) $exprNode->value);
            }

            if ($exprNode instanceof ConstExprFloatNode) {
                return new FloatLiteralT((float) $exprNode->value);
            }

            if ($exprNode instanceof ConstExprStringNode) {
                /** @var literal-string $literalString */
                $literalString = $exprNode->value;

                return new StringLiteralT($literalString);
            }
        }

        if ($typeNode instanceof GenericTypeNode) {
            return $this->reflectIdentifierType(
                $metadata,
                $typeNode->type->name,
                array_values(array_map(
                    fn (TypeNode $typeNode): Type => $this->reflectTypeNodeType($metadata, $typeNode),
                    $typeNode->genericTypes,
                )),
            );
        }

        if ($typeNode instanceof IdentifierTypeNode) {
            return $this->reflectIdentifierType($metadata, $typeNode->name);
        }

        throw new \LogicException(sprintf('%s is not supported by %s.', $typeNode::class, self::class));
    }

    private function reflectArrayShapeNodeType(Metadata $metadata, ArrayShapeNode $node): ArrayShapeT
    {
        $items = [];

        foreach ($node->items as $item) {
            $type = new ArrayShapeItem(
                type: $this->reflectTypeNodeType($metadata, $item->valueType),
                optional: $item->optional,
            );

            if ($item->keyName === null) {
                $items[] = $type;

                continue;
            }

            $keyName = $item->keyName;

            $key = match ($keyName::class) {
                ConstExprIntegerNode::class => (int) $keyName->value,
                ConstExprStringNode::class => $keyName->value,
                IdentifierTypeNode::class => $keyName->name,
                default => throw new \LogicException(sprintf('%s is not supported by %s.', $keyName::class, self::class)),
            };

            $items[$key] = $type;
        }

        return new ArrayShapeT($items);
    }

    /**
     * @return ($reflectionType is null ? null : Type)
     */
    private function reflectReflectionType(Metadata $metadata, ?\ReflectionType $reflectionType): ?Type
    {
        if ($reflectionType === null) {
            return null;
        }

        if ($reflectionType instanceof \ReflectionNamedType) {
            $type = $this->reflectIdentifierType($metadata, $reflectionType->getName());

            if ($reflectionType->allowsNull() && !$type instanceof NullT && !$type instanceof MixedT) {
                return new NullableT($type);
            }

            return $type;
        }

        if ($reflectionType instanceof \ReflectionUnionType) {
            return new UnionT(...array_map(
                fn (\ReflectionType $typePart): Type => $this->reflectReflectionType($metadata, $typePart),
                $reflectionType->getTypes(),
            ));
        }

        if ($reflectionType instanceof \ReflectionIntersectionType) {
            return new IntersectionT(...array_map(
                fn (\ReflectionType $typePart): Type => $this->reflectReflectionType($metadata, $typePart),
                $reflectionType->getTypes(),
            ));
        }

        throw new \LogicException(sprintf('%s is not supported by %s.', $reflectionType::class, self::class));
    }

    /**
     * @param list<Type> $templateArguments
     */
    private function reflectIdentifierType(Metadata $metadata, string $name, array $templateArguments = []): Type
    {
        if ($name === 'list') {
            return new ListT($templateArguments[0] ?? new MixedT());
        }

        if ($name === 'non-empty-list') {
            return new NonEmptyListT($templateArguments[0] ?? new MixedT());
        }

        if ($name === 'array') {
            return new ArrayT(...$this->resolveArrayTemplateArguments($templateArguments));
        }

        if ($name === 'non-empty-array') {
            return new NonEmptyArrayT(...$this->resolveArrayTemplateArguments($templateArguments));
        }

        if ($name === 'iterable') {
            if (\count($templateArguments) <= 1) {
                return new IterableT(valueType: $templateArguments[0] ?? new MixedT());
            }

            return new IterableT($templateArguments[0], $templateArguments[1]);
        }

        return match ($name) {
            '' => throw new \LogicException('Name cannot be empty.'),
            'null' => new NullT(),
            'true' => new TrueT(),
            'false' => new FalseT(),
            'bool' => new BoolT(),
            'float' => new FloatT(),
            'int' => new IntT(),
            'positive-int' => new PositiveIntT(),
            'numeric' => new NumericT(),
            'string' => new StringT(),
            'non-empty-string' => new NonEmptyStringT(),
            'numeric-string' => new NumericStringT(),
            'scalar' => new ScalarT(),
            'object' => new ObjectT(),
            'callable' => new CallableT(),
            'mixed' => new MixedT(),
            'void' => new VoidT(),
            'never' => new NeverT(),
            default => $metadata->tryReflectTemplateT($name)
                ?? $this->tryReflectClassType($metadata, $name, $templateArguments)
                ?? throw new \LogicException(sprintf('Failed to resolve name %s.', $name)),
        };
    }

    /**
     * @param list<Type> $templateArguments
     * @return array{Type<array-key>, Type}
     */
    private function resolveArrayTemplateArguments(array $templateArguments): array
    {
        if (\count($templateArguments) <= 1) {
            return [new ArrayKeyT(), $templateArguments[0] ?? new MixedT()];
        }

        $keyType = $templateArguments[0];

        if (!($keyType instanceof ArrayKeyT || $keyType instanceof IntT || $keyType instanceof StringT)) {
            throw new \LogicException(sprintf('Invalid array key type %s.', TypeStringifier::stringify($keyType)));
        }

        /** @var array{Type<array-key>, Type} */
        return [$keyType, $templateArguments[1]];
    }

    /**
     * @param list<Type> $templateArguments
     */
    private function tryReflectClassType(Metadata $metadata, string $name, array $templateArguments): null|NamedObjectT|StaticT
    {
        $resolvedName = $metadata->resolveName($name);

        if ($resolvedName instanceof StaticT) {
            return new StaticT($resolvedName->class, ...$templateArguments);
        }

        if (class_exists($resolvedName) || interface_exists($resolvedName)) {
            return new NamedObjectT(ltrim($resolvedName, '\\'), ...$templateArguments);
        }

        return null;
    }
}
