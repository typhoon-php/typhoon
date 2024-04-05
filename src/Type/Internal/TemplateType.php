<?php

declare(strict_types=1);

namespace Typhoon\Type\Internal;

use Typhoon\Type\DeclaredAt;
use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @psalm-immutable
 * @implements Type<mixed>
 */
final class TemplateType implements Type
{
    /**
     * @param non-empty-string $name
     * @param list<Type> $arguments
     */
    public function __construct(
        private readonly string $name,
        private readonly DeclaredAt $declaredAt,
        private readonly array $arguments,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->template($this, $this->name, $this->declaredAt, $this->arguments);
    }
}
