<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\Reflector;

use ExtendedTypeSystem\Reflection\NameContext;
use ExtendedTypeSystem\Reflection\Reflector;
use ExtendedTypeSystem\Type;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;

final class PhpDocTypeReflector
{
    /**
     * @return ($node is null ? null : Type)
     */
    public function reflectType(?TypeNode $node, NameContext $nameContext, Reflector $reflector): ?Type
    {
        return (new ContextualPhpDocTypeReflector($nameContext, $reflector))->reflectType($node);
    }
}
