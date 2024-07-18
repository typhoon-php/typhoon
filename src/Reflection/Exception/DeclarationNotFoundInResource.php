<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Exception;

use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\ConstId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\Reflection\Internal\Data\Data;
use Typhoon\Reflection\Resource;

/**
 * @api
 */
final class DeclarationNotFoundInResource extends \LogicException implements ReflectionException
{
    public function __construct(
        public readonly Resource $resource,
        public readonly ConstId|NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId $id,
    ) {
        $file = $resource->baseData[Data::File];

        parent::__construct(sprintf(
            '%s not found in %s',
            ucfirst($id->describe()),
            $file ?? substr($resource->code, 0, 50),
        ));
    }
}
