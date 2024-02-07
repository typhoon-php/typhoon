<?php

declare(strict_types=1);

namespace Typhoon\Type;

/**
 * @api
 * @psalm-immutable
 * @template-covariant TObject of object
 * @implements Type<TObject>
 */
final class NamedObjectType implements Type
{
    /**
     * @var class-string<TObject>
     */
    public readonly string $class;

    /**
     * @var list<Type>
     */
    public readonly array $templateArguments;

    /**
     * @internal
     * @psalm-internal Typhoon\Type
     * @param class-string<TObject> $class
     * @param list<Type> $templateArguments
     */
    public function __construct(
        string $class,
        array $templateArguments = [],
    ) {
        $this->templateArguments = $templateArguments;
        $this->class = $class;
    }

    public function accept(TypeVisitor $visitor): mixed
    {
        return $visitor->visitNamedObject($this);
    }
}
