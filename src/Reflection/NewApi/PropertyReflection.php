<?php

declare(strict_types=1);

namespace Typhoon\Reflection\NewApi;

use Typhoon\Reflection\Origin;
use Typhoon\Type\Type;

/**
 * @api
 */
abstract class PropertyReflection
{
    /**
     * @param non-empty-string $name
     * @param class-string $class
     * @param ?non-empty-string $phpDoc
     * @param ?positive-int $startLine
     * @param ?positive-int $endLine
     */
    public function __construct(
        public readonly string $name,
        public readonly string $class,
        public readonly ?string $phpDoc,
        public readonly ?int $startLine,
        public readonly ?int $endLine,
    ) {}

    abstract public function class(): ClassReflection;

    abstract public function isDeprecated(): bool;

    abstract public function isPrivate(): bool;

    abstract public function isPromoted(): bool;

    abstract public function isProtected(): bool;

    abstract public function isPublic(): bool;

    abstract public function isReadonly(Origin $origin = Origin::Resolved): bool;

    /**
     * @return ($origin is Origin::Resolved ? Type : null|Type)
     */
    abstract public function type(Origin $origin = Origin::Resolved): ?Type;

    abstract public function hasDefaultValue(): bool;
}
