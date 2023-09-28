<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TType
 * @implements Type<TType>
 */
final class TemplateType implements Type
{
    /**
     * @var non-empty-string
     */
    public readonly string $name;

    /**
     * @internal
     * @psalm-internal Typhoon\Type
     * @param non-empty-string $name
     */
    public function __construct(
        string $name,
    ) {
        $this->name = $name;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitTemplate($this);
    }
}
