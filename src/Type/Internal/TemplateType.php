<?php

declare(strict_types=1);

namespace Typhoon\Type\Internal;

use Typhoon\Type\AtClass;
use Typhoon\Type\AtFunction;
use Typhoon\Type\AtMethod;
use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Type
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
        private readonly AtFunction|AtClass|AtMethod $declaredAt,
        private readonly array $arguments,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->template($this, $this->name, $this->declaredAt, $this->arguments);
    }
}
