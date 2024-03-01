<?php

declare(strict_types=1);

namespace Typhoon\Reflection\NameContext;

interface NameResolver
{
    /**
     * @return non-empty-string
     * @throws InvalidName
     */
    public function resolveNameAsClass(string $name): string;

    /**
     * @return array{0: non-empty-string, 1?: non-empty-string}
     * @throws InvalidName
     */
    public function resolveNameAsConstant(string $name): array;
}
