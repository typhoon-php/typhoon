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

    public readonly AtMethod|AtClass|AtFunction $declaredAt;

    /**
     * @var Type<TType>
     */
    public readonly Type $constraint;

    /**
     * @internal
     * @psalm-internal Typhoon\Type
     * @param non-empty-string $name
     * @param Type<TType> $constraint
     */
    public function __construct(
        string $name,
        AtFunction|AtClass|AtMethod $declaredAt,
        Type $constraint,
    ) {
        $this->name = $name;
        $this->declaredAt = $declaredAt;
        $this->constraint = $constraint;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitTemplate($this);
    }
}
