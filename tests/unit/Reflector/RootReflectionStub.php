<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Reflector;

use Typhoon\Reflection\ChangeDetector;

final class RootReflectionStub implements RootReflection
{
    private readonly ChangeDetector $changeDetector;

    /**
     * @psalm-suppress UnusedProperty
     */
    private readonly string $data;

    /**
     * @param non-empty-string $name
     */
    public function __construct(
        private readonly string $name,
        bool $changed,
    ) {
        $this->changeDetector = new ChangeDetectorStub($changed);
        $this->data = random_bytes(128);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getChangeDetector(): ChangeDetector
    {
        return $this->changeDetector;
    }
}
