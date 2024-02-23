<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @implements Type<mixed>
 */
final class ConditionalType implements Type
{
    public function __construct(
        private readonly Argument|TemplateType $subject,
        private readonly Type $if,
        private readonly Type $then,
        private readonly Type $else,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->conditional($this, $this->subject, $this->if, $this->then, $this->else);
    }
}
