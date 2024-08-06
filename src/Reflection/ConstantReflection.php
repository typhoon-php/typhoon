<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\ChangeDetector\ChangeDetector;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Misc\NonSerializable;
use Typhoon\Type\Type;
use Typhoon\TypedMap\TypedMap;

/**
 * @api
 */
final class ConstantReflection
{
    use NonSerializable;

    public readonly ConstantId $id;

    /**
     * This internal property is public for testing purposes.
     * It will likely be available as part of the API in the near future.
     *
     * @internal
     * @psalm-internal Typhoon
     */
    public readonly TypedMap $data;

    /**
     * @internal
     * @psalm-internal Typhoon\Reflection
     */
    public function __construct(
        ConstantId $id,
        TypedMap $data,
        private readonly TyphoonReflector $reflector,
    ) {
        $this->id = $id;
        $this->data = $data;
    }

    /**
     * @return ?non-empty-string
     */
    public function extension(): ?string
    {
        return $this->data[Data::PhpExtension];
    }

    public function namespace(): string
    {
        return $this->data[Data::Namespace];
    }

    public function changeDetector(): ChangeDetector
    {
        return $this->data[Data::ChangeDetector];
    }

    public function location(): ?Location
    {
        return $this->data[Data::Location];
    }

    public function isInternallyDefined(): bool
    {
        return $this->data[Data::InternallyDefined];
    }

    /**
     * @return ?non-empty-string
     */
    public function phpDoc(): ?string
    {
        return $this->data[Data::PhpDoc]?->getText();
    }

    /**
     * This method returns the actual class constant's value and thus might trigger autoloading or throw errors.
     */
    public function evaluate(): mixed
    {
        return $this->data[Data::ValueExpression]->evaluate($this->reflector);
    }

    /**
     * @return ($kind is TypeKind::Resolved ? Type : ?Type)
     */
    public function type(TypeKind $kind = TypeKind::Resolved): ?Type
    {
        return $this->data[Data::Type]->get($kind);
    }

    public function isDeprecated(): bool
    {
        return $this->data[Data::Deprecation] !== null;
    }

    public function deprecation(): ?Deprecation
    {
        return $this->data[Data::Deprecation];
    }
}
