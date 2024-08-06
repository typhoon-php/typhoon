<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\PhpParser;

use Typhoon\DeclarationId\Id;
use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal\PhpParser
 * @implements Type<mixed>
 */
final class UnresolvedConstantType implements Type
{
    /**
     * @param non-empty-string $namespacedName
     * @param non-empty-string $globalName
     */
    public function __construct(
        private readonly string $namespacedName,
        private readonly string $globalName,
    ) {}

    public function accept(TypeVisitor $visitor): mixed
    {
        if (\defined($this->namespacedName)) {
            return $visitor->constant($this, Id::constant($this->namespacedName));
        }

        return $visitor->constant($this, Id::constant($this->globalName));
    }
}
