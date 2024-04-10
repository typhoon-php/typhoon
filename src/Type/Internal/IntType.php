<?php

declare(strict_types=1);

namespace Typhoon\Type\Internal;

use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @readonly
 * @implements Type<int>
 */
final class IntType implements Type
{
    public function __construct(
        private readonly ?int $min,
        private readonly ?int $max,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        if ((new \ReflectionMethod($visitor, 'int'))->getNumberOfParameters() !== 3) {
            trigger_deprecation('typhoon/reflection', '0.3.3', 'Not declaring $min and $max parameters in method %s::int() is deprecated.', self::class);

            /** @psalm-suppress DeprecatedMethod */
            return $visitor->intRange($this, $this->min, $this->max);
        }

        /**
         * @psalm-suppress TooManyArguments
         * @phpstan-ignore arguments.count
         */
        return $visitor->int($this, $this->min, $this->max);
    }
}
