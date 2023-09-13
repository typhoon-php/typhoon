<?php

declare(strict_types=1);

namespace Typhoon\Type;

use Typhoon\Type;
use Typhoon\TypeVisitor;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TObject of object
 * @implements Type<class-string<TObject>>
 */
final class NamedClassStringType implements Type
{
    /**
     * @var Type<TObject>
     */
    public readonly Type $type;

    /**
     * @internal
     * @psalm-internal Typhoon
     * @param Type<TObject> $type
     */
    public function __construct(
        Type $type,
    ) {
        $this->type = $type;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitNamedClassString($this);
    }
}
