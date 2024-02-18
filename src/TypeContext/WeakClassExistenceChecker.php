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
    private readonly \WeakReference $session;

    public function __construct(
        ClassExistenceChecker $session,
    ) {
        $this->session = \WeakReference::create($session);
    }

    public function classExists(string $name): bool
    {
        return $this->session->get()?->classExists($name) ?? throw new \RuntimeException();
    }
}
