<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Type\Type;
use Typhoon\Type\Variance;

/**
 * @api
 * @psalm-immutable
 */
final class TemplateReflection
{
    /**
     * @var non-empty-string
     */
    public readonly string $name;

    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     * @param non-negative-int $position
     * @param non-empty-string $name
     */
    public function __construct(
        string $name,
        private readonly int $position,
        private readonly Type $constraint,
        private readonly Variance $variance,
    ) {
        $this->name = $name;
    }

    public function getConstraint(): Type
    {
        return $this->constraint;
    }

    /**
     * @return non-empty-string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return non-negative-int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    public function getVariance(): Variance
    {
        return $this->variance;
    }

    private function __clone() {}
}
