<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TType
 * @implements Type<TType>
 */
final class MethodTemplateType implements Type
{
    /**
     * @var class-string
     */
    public readonly string $class;

    /**
     * @var non-empty-string
     */
    public readonly string $method;

    /**
     * @var non-empty-string
     */
    public readonly string $name;

    /**
     * @internal
     * @psalm-internal Typhoon\Type
     * @param class-string $class
     * @param non-empty-string $method
     * @param non-empty-string $name
     */
    public function __construct(
        string $class,
        string $method,
        string $name,
    ) {
        $this->name = $name;
        $this->method = $method;
        $this->class = $class;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitMethodTemplate($this);
    }
}
