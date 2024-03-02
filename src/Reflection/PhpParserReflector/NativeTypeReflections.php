<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpParserReflector;

use PhpParser\Node;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;
use Typhoon\Reflection\TypeContext\TypeContext;
use Typhoon\Type\Type;
use Typhoon\Type\types;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\PhpParserReflector
 */
final class NativeTypeReflections
{
    private function __construct() {}

    public static function reflect(TypeContext $typeContext, Name|Identifier|ComplexType $node, bool $implicitlyNullable = false): Type
    {
        if ($node instanceof NullableType) {
            return types::nullable(self::reflect($typeContext, $node->type));
        }

        if ($implicitlyNullable) {
            return types::nullable(self::reflect($typeContext, $node));
        }

        if ($node instanceof UnionType) {
            return types::union(...array_map(
                static fn(Node $child): Type => self::reflect($typeContext, $child),
                $node->types,
            ));
        }

        if ($node instanceof IntersectionType) {
            return types::intersection(...array_map(
                static fn(Node $child): Type => self::reflect($typeContext, $child),
                $node->types,
            ));
        }

        if ($node instanceof Identifier) {
            return match ($node->name) {
                'never' => types::never,
                'void' => types::void,
                'null' => types::null,
                'true' => types::true,
                'false' => types::false,
                'bool' => types::bool,
                'int' => types::int,
                'float' => types::float,
                'string' => types::string,
                'array' => types::array,
                'object' => types::object,
                'callable' => types::callable,
                'iterable' => types::iterable,
                'resource' => types::resource,
                'mixed' => types::mixed,
                default => throw new UnsupportedNativeType(sprintf('Type "%s" is not supported', $node->name)),
            };
        }

        if ($node instanceof Name) {
            return $typeContext->resolveNameAsType($node->toCodeString(), classOnly: true);
        }

        throw new UnsupportedNativeType(sprintf('Type node of class %s is not supported', $node::class));
    }
}
