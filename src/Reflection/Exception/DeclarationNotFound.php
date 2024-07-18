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
final class DeclarationNotFound extends \RuntimeException implements ReflectionException
{
    public function __construct(
        public readonly ConstId|NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $id,
    ) {
        parent::__construct(sprintf('%s not found', ucfirst($id->describe())));
    }
}
