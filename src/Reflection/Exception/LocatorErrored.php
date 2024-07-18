<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Exception;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\ConstId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;

/**
 * @api
 */
final class LocatorErrored extends \RuntimeException implements ReflectionException
{
    public function __construct(
        public readonly ConstId|NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $declarationId,
        \Throwable $error,
    ) {
        parent::__construct(sprintf('An error occurred when locating %s', $declarationId->describe()), previous: $error);
    }
}
