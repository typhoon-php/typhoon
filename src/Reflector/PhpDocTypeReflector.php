<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use Typhoon\Reflection\NameResolution\NameContext;
use Typhoon\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class PhpDocTypeReflector
{
    /**
     * @return ($node is null ? null : Type)
     */
    public function reflectType(?TypeNode $node, NameContext $nameContext, ReflectionContext $reflectionContext): ?Type
    {
        return (new ContextAwarePhpDocTypeReflector($nameContext, $reflectionContext))->reflectType($node);
    }
}
