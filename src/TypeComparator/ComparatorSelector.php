<?php

declare(strict_types=1);

namespace Typhoon\TypeComparator;

use Typhoon\Type\DefaultTypeVisitor;
use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\TypeComparator
 * @extends DefaultTypeVisitor<TypeVisitor<bool>>
 */
final class ComparatorSelector extends DefaultTypeVisitor
{
    public function bool(Type $self): mixed
    {
        return new IsBool();
    }

    public function float(Type $self): mixed
    {
        return new IsFloat();
    }

    public function int(Type $self): mixed
    {
        return new IsInt();
    }

    public function intersection(Type $self, array $types): mixed
    {
        return new IsIntersection($types);
    }

    public function intRange(Type $self, ?int $min, ?int $max): mixed
    {
        return new IsIntRange($min, $max);
    }

    public function literal(Type $self, Type $type): mixed
    {
        return new IsLiteral($type);
    }

    public function literalValue(Type $self, float|bool|int|string $value): mixed
    {
        return new IsLiteralValue($value);
    }

    public function mixed(Type $self): mixed
    {
        return new IsMixed();
    }

    public function never(Type $self): mixed
    {
        return new IsNever();
    }

    public function nonEmpty(Type $self, Type $type): mixed
    {
        return new IsNonEmpty($type);
    }

    public function null(Type $self): mixed
    {
        return new IsNull();
    }

    public function numericString(Type $self): mixed
    {
        return new IsNumericString();
    }

    public function object(Type $self): mixed
    {
        return new IsObject();
    }

    public function resource(Type $self): mixed
    {
        return new IsResource();
    }

    public function string(Type $self): mixed
    {
        return new IsString();
    }

    public function truthyString(Type $self): mixed
    {
        return new IsTruthyString();
    }

    public function union(Type $self, array $types): mixed
    {
        return new IsUnion($types);
    }

    public function void(Type $self): mixed
    {
        return new IsVoid();
    }

    protected function default(Type $self): mixed
    {
        return new /** @extends DefaultTypeVisitor<bool> */ class () extends DefaultTypeVisitor {
            protected function default(Type $self): bool
            {
                return false;
            }
        };
    }
}
