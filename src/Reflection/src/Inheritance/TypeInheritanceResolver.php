<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Inheritance;

use Typhoon\Reflection\Metadata\TypeMetadata;
use Typhoon\Reflection\TypeResolver\TemplateResolver;
use Typhoon\Type\Type;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Inheritance
 */
final class TypeInheritanceResolver
{
    private ?TypeMetadata $own = null;

    /**
     * @var list<array{TypeMetadata, TemplateResolver}>
     */
    private array $inherited = [];

    private static function typesEqual(?Type $a, ?Type $b): bool
    {
        // Comparison operator == is intentionally used here.
        // Of course, we need a proper type comparator,
        // but for now simple equality check should do the job 90% of the time.
        return $a == $b;
    }

    public function setOwn(TypeMetadata $type): void
    {
        $this->own = $type;
    }

    public function addInherited(TypeMetadata $type, TemplateResolver $templateResolver): void
    {
        $this->inherited[] = [$type, $templateResolver];
    }

    public function resolve(): TypeMetadata
    {
        if ($this->own !== null) {
            if ($this->inherited === []) {
                return $this->own;
            }

            if ($this->own->phpDoc !== null) {
                return $this->own;
            }

            $ownNativeType = $this->own->native;

            foreach ($this->inherited as [$type, $templateResolver]) {
                // If own type is different (weakened parameter type or strengthened return type), we want to keep it.
                // This should be compared according to variance with a proper type comparator,
                // but for now simple inequality check should do the job 90% of the time.
                if (!self::typesEqual($type->native, $ownNativeType)) {
                    continue;
                }

                // If inherited type resolves to same native type, we should continue to look for something more interesting.
                if (self::typesEqual($type->resolved, $ownNativeType)) {
                    continue;
                }

                return $this->own->withResolved($type->resolved->accept($templateResolver));
            }

            return $this->own;
        }

        \assert($this->inherited !== []);

        if (\count($this->inherited) !== 1) {
            foreach ($this->inherited as [$type, $templateResolver]) {
                // If inherited type resolves to its native type, we should continue to look for something more interesting.
                if (!self::typesEqual($type->resolved, $type->native)) {
                    return $type->withResolved($type->resolved->accept($templateResolver));
                }
            }
        }

        [$type, $templateResolver] = $this->inherited[0];

        return $type->withResolved($type->resolved->accept($templateResolver));
    }
}
