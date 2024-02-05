<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Inheritance;

use Typhoon\Reflection\TypeReflection;
use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Inheritance
 */
final class TypeInheritanceResolver
{
    private ?TypeReflection $own = null;

    /**
     * @var list<array{TypeReflection, TypeVisitor<Type>}>
     */
    private array $inherited = [];

    private static function typesEqual(?Type $a, ?Type $b): bool
    {
        // Comparison operator == is intentionally used here.
        // Of course, we need a proper type comparator,
        // but for now simple equality check should do the job 90% of the time.
        return $a == $b;
    }

    public function setOwn(TypeReflection $type): void
    {
        $this->own = $type;
    }

    /**
     * @param TypeVisitor<Type> $templateResolver
     */
    public function addInherited(TypeReflection $type, TypeVisitor $templateResolver): void
    {
        $this->inherited[] = [$type, $templateResolver];
    }

    public function resolve(): TypeReflection
    {
        if ($this->own !== null) {
            if ($this->inherited === []) {
                return $this->own;
            }

            if ($this->own->getPhpDoc() !== null) {
                return $this->own;
            }

            $ownNativeType = $this->own->getNative();

            foreach ($this->inherited as [$type, $templateResolver]) {
                // If own type is different (weakened parameter type or strengthened return type), we want to keep it.
                // This should be compared according to variance with a proper type comparator,
                // but for now simple inequality check should do the job 90% of the time.
                if (!self::typesEqual($type->getNative(), $ownNativeType)) {
                    continue;
                }

                // If inherited type resolves to same native type, we should continue to look for something more interesting.
                if (self::typesEqual($type->getResolved(), $ownNativeType)) {
                    continue;
                }

                return $this->own->withResolved($type->getResolved()->accept($templateResolver));
            }

            return $this->own;
        }

        \assert($this->inherited !== []);

        if (\count($this->inherited) !== 1) {
            foreach ($this->inherited as [$type, $templateResolver]) {
                // If inherited type resolves to its native type, we should continue to look for something more interesting.
                if (!self::typesEqual($type->getResolved(), $type->getNative())) {
                    return $type->withResolved($type->getResolved()->accept($templateResolver));
                }
            }
        }

        [$type, $templateResolver] = $this->inherited[0];

        return $type->withResolved($type->getResolved()->accept($templateResolver));
    }
}
