<?php

declare(strict_types=1);

namespace Typhoon\TypeMap;

/**
 * @api
 */
final class UndefinedKey extends \RuntimeException
{
    public function __construct(
        public readonly Key $key,
    ) {
        parent::__construct(sprintf('Key %s::%s is not defined in type map', $key::class, $key->name));
    }
}
