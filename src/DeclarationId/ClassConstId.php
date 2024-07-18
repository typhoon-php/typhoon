<?php

declare(strict_types=1);

namespace Typhoon\DeclarationId;

/**
 * @api
 */
final class ClassConstId extends Id
{
    /**
     * @param non-empty-string $name
     */
    protected function __construct(
        public readonly NamedClassId|AnonymousClassId $class,
        public readonly string $name,
    ) {}

    public function describe(): string
    {
        if ($this->class instanceof AnonymousClassId) {
            return sprintf('constant %s of %s', $this->name, $this->class->describe());
        }

        return sprintf('constant %s::%s', $this->class->name, $this->name);
    }

    public function equals(mixed $value): bool
    {
        return $value instanceof self
            && $value->class->equals($this->class)
            && $value->name === $this->name;
    }

    public function reflect(): \ReflectionClassConstant
    {
        $class = $this->class->name ?? throw new \LogicException(sprintf(
            "Cannot reflect %s, because it's runtime name is not available",
            $this->describe(),
        ));

        /** @psalm-suppress ArgumentTypeCoercion */
        return new \ReflectionClassConstant($class, $this->name);
    }

    public function jsonSerialize(): array
    {
        return [self::CODE_CLASS_CONST, $this->class, $this->name];
    }
}
