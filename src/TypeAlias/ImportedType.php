<?php

declare(strict_types=1);

namespace Typhoon\Reflection\TypeAlias;

use Typhoon\Reflection\Exception\DefaultReflectionException;
use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 * @psalm-immutable
 * @implements Type<mixed>
 */
final class ImportedType implements Type
{
    /**
     * @param class-string $class
     * @param non-empty-string $name
     */
    public function __construct(
        public readonly string $class,
        public readonly string $name,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        if ($visitor instanceof ImportedTypeResolver) {
            return $visitor->visitImportedType($this)->accept($visitor);
        }

        throw new DefaultReflectionException(self::class);
    }
}
