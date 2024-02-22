<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @implements Type<mixed>
 */
final class OffsetType implements Type
{
    public readonly Type $subject;

    public readonly Type $offset;

    /**
     * @internal
     * @psalm-internal Typhoon\Type
     */
    public function __construct(
        Type $subject,
        Type $offset,
    ) {
        $this->subject = $subject;
        $this->offset = $offset;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitOffset($this);
    }
}
