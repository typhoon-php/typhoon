<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Inheritance;

use Typhoon\Reflection\Metadata\ClassConstantMetadata;
use Typhoon\Reflection\TypeResolver\TemplateResolver;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Inheritance
 */
final class ClassConstantInheritanceResolver
{
    private ?ClassConstantMetadata $constant = null;

    private TypeInheritanceResolver $type;

    /**
     * @param class-string $class
     */
    public function __construct(
        private readonly string $class,
    ) {
        $this->type = new TypeInheritanceResolver();
    }

    public function setOwn(ClassConstantMetadata $constant): void
    {
        $this->constant = $constant;
        $this->type->setOwn($constant->type);
    }

    public function addUsed(ClassConstantMetadata $constant, TemplateResolver $templateResolver): void
    {
        $this->constant ??= $constant->withClass($this->class);
        $this->type->addInherited($constant->type, $templateResolver);
    }

    public function addInherited(ClassConstantMetadata $constant, TemplateResolver $templateResolver): void
    {
        if ($constant->modifiers & \ReflectionClassConstant::IS_PRIVATE) {
            return;
        }

        $this->constant ??= $constant;
        $this->type->addInherited($constant->type, $templateResolver);
    }

    public function resolve(): ?ClassConstantMetadata
    {
        if ($this->constant === null) {
            return null;
        }

        return $this->constant->withType($this->type->resolve());
    }
}
