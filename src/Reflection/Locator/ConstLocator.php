<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Locator;

use Typhoon\DeclarationId\ConstId;
use Typhoon\Reflection\Resource;

/**
 * @api
 */
interface ConstLocator
{
    public function locate(ConstId $id): ?Resource;
}
