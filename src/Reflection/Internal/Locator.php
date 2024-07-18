<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\ConstId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Exception\DeclarationNotFound;
use Typhoon\Reflection\Exception\LocatorErrored;
use Typhoon\Reflection\Locator\AnonymousLocator;
use Typhoon\Reflection\Locator\ConstLocator;
use Typhoon\Reflection\Locator\NamedClassLocator;
use Typhoon\Reflection\Locator\NamedFunctionLocator;
use Typhoon\Reflection\Resource;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class Locator
{
    /**
     * @var list<ConstLocator>
     */
    private array $constLocators = [];

    /**
     * @var list<NamedFunctionLocator>
     */
    private array $namedFunctionLocators = [];

    /**
     * @var list<NamedClassLocator>
     */
    private array $namedClassLocators = [];

    /**
     * @var list<AnonymousLocator>
     */
    private array $anonymousLocators = [];

    /**
     * @param iterable<ConstLocator|NamedFunctionLocator|NamedClassLocator|AnonymousLocator> $locators
     */
    public function __construct(iterable $locators)
    {
        foreach ($locators as $locator) {
            $this->add($locator);
        }
    }

    public function locate(ConstId|NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $id): Resource
    {
        $locators = match (true) {
            $id instanceof ConstId => $this->constLocators,
            $id instanceof NamedFunctionId => $this->namedFunctionLocators,
            $id instanceof NamedClassId => $this->namedClassLocators,
            $id instanceof AnonymousFunctionId,
            $id instanceof AnonymousClassId => $this->anonymousLocators,
        };

        foreach ($locators as $locator) {
            try {
                /** @psalm-suppress PossiblyInvalidArgument */
                $resource = $locator->locate($id);
            } catch (\Throwable $exception) {
                throw new LocatorErrored($id, $exception);
            }

            if ($resource !== null) {
                return $resource;
            }
        }

        throw new DeclarationNotFound($id);
    }

    public function with(ConstLocator|NamedFunctionLocator|NamedClassLocator|AnonymousLocator $locator): self
    {
        $copy = clone $this;
        $copy->add($locator);

        return $copy;
    }

    private function add(ConstLocator|NamedFunctionLocator|NamedClassLocator|AnonymousLocator $locator): void
    {
        if ($locator instanceof ConstLocator) {
            $this->constLocators[] = $locator;
        }

        if ($locator instanceof NamedFunctionLocator) {
            $this->namedFunctionLocators[] = $locator;
        }

        if ($locator instanceof NamedClassLocator) {
            $this->namedClassLocators[] = $locator;
        }

        if ($locator instanceof AnonymousLocator) {
            $this->anonymousLocators[] = $locator;
        }
    }
}
