<?php

declare(strict_types=1);

namespace Typhoon\Reflection\PhpParserReflector;

use PhpParser\Node;
use PhpParser\Node\Name;
use Typhoon\Reflection\Exception\DefaultReflectionException;
use Typhoon\Reflection\TypeContext\TypeContext;
use Typhoon\Type\Type;
use Typhoon\Type\types;

final class NativeTypeReflections
{
    /**
     * @psalm-suppress UnusedConstructor
     */
    private function __construct() {}

    public static function reflect(TypeContext $typeContext, Node $node, bool $implicitlyNullable = false): Type
    {
        if ($node instanceof Node\NullableType) {
            return types::nullable(self::reflect($typeContext, $node->type));
        }

        if ($implicitlyNullable) {
            return types::nullable(self::reflect($typeContext, $node));
        }

        if ($node instanceof Node\UnionType) {
            return types::union(...array_map(
                static fn(Node $child): Type => self::reflect($typeContext, $child),
                $node->types,
            ));
        }

        if ($node instanceof Node\IntersectionType) {
            return types::intersection(...array_map(
                static fn(Node $child): Type => self::reflect($typeContext, $child),
                $node->types,
            ));
        }

        if ($node instanceof Node\Identifier) {
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
                'array' => types::array(),
                'object' => types::object,
                'callable' => types::callable(),
                'iterable' => types::iterable(),
                'resource' => types::resource,
                'mixed' => types::mixed,
                default => throw new DefaultReflectionException(sprintf(
                    '%s with name "%s" is not supported.',
                    $node->name,
                    $node::class,
                )),
            };
        }

        if ($node instanceof Name) {
            $resolvedName = $typeContext->resolveNameAsClass($node->toCodeString());

            if ($node->toString() === 'static') {
                return types::static($resolvedName);
            }

            return types::object($resolvedName);
        }

        throw new DefaultReflectionException(sprintf('%s is not supported.', $node::class));
    }
}
