<?php

declare(strict_types=1);

namespace Typhoon\DataStructure;

use Typhoon\DataStructure\Internal\ValueStringifier;

/**
 * @api
 */
final class KeyIsNotDefined extends \RuntimeException
{
    public function __construct(mixed $key)
    {
        parent::__construct(\sprintf('Key %s is not defined', ValueStringifier::stringify($key)));
    }
}
