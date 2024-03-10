<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use Typhoon\Type\DefaultTypeVisitor;
use Typhoon\Type\Type;

/**
 * @internal
 * @psalm-internal Typhoon\TypeComparator
 */
final class IsLiteralValue extends Comparator
{
    public function __construct(
        private readonly float|bool|int|string $value,
    ) {}

    public function classConstant(Type $self, Type $class, string $name): mixed
    {
        // TODO full class constant support.
        return $name === 'class' && \is_string($this->value) && $this->value === $class->accept(
            new /** @extends DefaultTypeVisitor<?string> */ class () extends DefaultTypeVisitor {
                public function namedObject(Type $self, string $class, array $arguments): mixed
                {
                    return $class;
                }

                protected function default(Type $self): mixed
                {
                    return null;
                }
            },
        );
    }

    public function literalValue(Type $self, float|bool|int|string $value): mixed
    {
        return $value === $this->value;
    }
}
