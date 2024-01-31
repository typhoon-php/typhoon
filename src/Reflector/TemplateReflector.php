<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use Typhoon\Reflection\TemplateReflection;
use Typhoon\Reflection\Variance;
use Typhoon\Type\AtClass;
use Typhoon\Type\AtFunction;
use Typhoon\Type\AtMethod;
use Typhoon\Type\TemplateType;
use Typhoon\Type\Type;
use Typhoon\Type\types;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class TemplateReflector
{
    private ?TemplateType $type = null;

    /**
     * @param non-negative-int $position
     * @param non-empty-string $name
     * @param Type|\Closure(): Type $constraint
     */
    public function __construct(
        private readonly int $position,
        public readonly string $name,
        private readonly AtFunction|AtClass|AtMethod $declaredAt,
        private readonly Variance $variance,
        private \Closure|Type $constraint,
    ) {}

    public function type(): TemplateType
    {
        return $this->type ??= types::template($this->name, $this->declaredAt, $this->constraint());
    }

    public function reflection(): TemplateReflection
    {
        return new TemplateReflection(
            position: $this->position,
            name: $this->name,
            constraint: $this->constraint(),
            variance: $this->variance,
        );
    }

    private function constraint(): Type
    {
        if ($this->constraint instanceof Type) {
            return $this->constraint;
        }

        return $this->constraint = ($this->constraint)();
    }
}
