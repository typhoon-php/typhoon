<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Visitor;

use Typhoon\Type\Type;
use Typhoon\Type\Visitor\DefaultTypeVisitor;
use function Typhoon\Type\stringify;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Visitor
 * @extends DefaultTypeVisitor<positive-int>
 */
final class IntMaskResolver extends DefaultTypeVisitor
{
    public function intValue(Type $type, int $value): mixed
    {
        if ($value <= 0) {
            return $this->default($type);
        }

        return $value;
    }

    public function union(Type $type, array $ofTypes): mixed
    {
        $mask = 0;

        foreach ($ofTypes as $ofType) {
            $mask |= $ofType->accept($this);
        }

        if ($mask <= 0) {
            return $this->default($type);
        }

        return $mask;
    }

    protected function default(Type $type): mixed
    {
        throw new \LogicException(\sprintf('Cannot resolve %s type as int-mask type argument', stringify($type)));
    }
}
