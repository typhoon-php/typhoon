<?php

declare(strict_types=1);

namespace Typhoon\Type;

use Typhoon\Type;
use Typhoon\TypeVisitor;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TType
 * @implements Type<TType>
 */
final class ClassTemplateType implements Type
{
    /**
     * @var class-string
     */
    public readonly string $class;

    /**
     * @var non-empty-string
     */
    public readonly string $name;

    /**
     * @internal
     * @psalm-internal Typhoon
     * @param class-string $class
     * @param non-empty-string $name
     */
    public function __construct(
        string $class,
        string $name,
    ) {
        $this->name = $name;
        $this->class = $class;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitClassTemplate($this);
    }
}
