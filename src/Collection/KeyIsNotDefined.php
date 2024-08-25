<?php

declare(strict_types=1);

namespace Typhoon\Collection;

/**
 * @api
 */
final class KeyIsNotDefined extends \RuntimeException
{
    public function __construct(mixed $key)
    {
        parent::__construct(\sprintf('Key %s is not defined in the Collection', ValueStringifier::stringify($key)));
    }
}
