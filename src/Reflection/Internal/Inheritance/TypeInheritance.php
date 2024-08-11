<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Inheritance;

use Typhoon\Reflection\Internal\Data\TypeData;
use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal\Inheritance
 */
final class TypeInheritance
{
    private ?TypeData $own = null;

    /**
     * @var list<array{TypeData, TypeVisitor<Type>}>
     */
    private array $inherited = [];

    private static function typesEqual(?Type $a, ?Type $b): bool
    {
        // Comparison operator == is intentionally used here.
        // Of course, we need a proper type comparator,
        // but for now simple equality check should do the job 90% of the time.
        return $a == $b;
    }

    public function applyOwn(TypeData $data): void
    {
        $this->own = $data;
    }

    /**
     * @param TypeVisitor<Type> $typeResolver
     */
    public function applyInherited(TypeData $data, TypeVisitor $typeResolver): void
    {
        $this->inherited[] = [$data, $typeResolver];
    }

    public function build(): TypeData
    {
        if ($this->own !== null) {
            if ($this->own->annotated !== null) {
                return $this->own;
            }

            $ownResolved = $this->own->get();

            foreach ($this->inherited as [$inherited, $typeResolver]) {
                // If own type is different (weakened parameter type or strengthened return type), we want to keep it.
                // This should be compared according to variance with a proper type comparator,
                // but for now simple inequality check should do the job 90% of the time.
                if (!self::typesEqual($inherited->native, $this->own->native)) {
                    continue;
                }

                // If inherited type resolves to equal own type, we should continue to look for something more interesting.
                if (self::typesEqual($inherited->get(), $ownResolved)) {
                    continue;
                }

                return $inherited->withTentative(null)->inherit($typeResolver);
            }

            return $this->own;
        }

        \assert($this->inherited !== []);

        if (\count($this->inherited) !== 1) {
            foreach ($this->inherited as [$inherited, $typeResolver]) {
                // If inherited type resolves to its native type, we should continue to look for something more interesting.
                if (self::typesEqual($inherited->get(), $inherited->native)) {
                    continue;
                }

                return $inherited->inherit($typeResolver);
            }
        }

        [$inherited, $typeResolver] = $this->inherited[0];

        return $inherited->inherit($typeResolver);
    }
}
