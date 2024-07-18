<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\ConstId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\DeclarationId\IdMap;
use Typhoon\Reflection\Resource;

/**
 * @api
 */
final class DeterministicLocator implements ConstLocator, NamedFunctionLocator, NamedClassLocator, AnonymousLocator
{
    /**
     * @param IdMap<ConstId|NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId, \Typhoon\Reflection\Resource> $resources
     */
    public function __construct(
        private IdMap $resources,
    ) {}

    public function locate(ConstId|NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $id): ?Resource
    {
        return $this->resources[$id] ?? null;
    }
}
