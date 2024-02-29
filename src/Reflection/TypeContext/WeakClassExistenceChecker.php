<?php

declare(strict_types=1);

namespace Typhoon\Reflection\TypeContext;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class WeakClassExistenceChecker implements ClassExistenceChecker
{
    /**
     * @var \WeakReference<ClassExistenceChecker>
     */
    private readonly \WeakReference $classExistenceChecker;

    public function __construct(ClassExistenceChecker $classExistenceChecker)
    {
        $this->classExistenceChecker = \WeakReference::create($classExistenceChecker);
    }

    public function classExists(string $name): bool
    {
        return $this->classExistenceChecker->get()?->classExists($name) ?? false;
    }
}
