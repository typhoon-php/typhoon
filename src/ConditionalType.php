<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @psalm-immutable
 * @implements Type<mixed>
 */
final class ConditionalType implements Type
{
    public readonly Argument|TemplateType $subject;

    public readonly Type $if;

    public readonly Type $then;

    public readonly Type $else;

    /**
     * @internal
     * @psalm-internal Typhoon\Type
     */
    public function __construct(
        Argument|TemplateType $subject,
        Type $if,
        Type $then,
        Type $else,
    ) {
        $this->subject = $subject;
        $this->if = $if;
        $this->then = $then;
        $this->else = $else;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitConditional($this);
    }
}
