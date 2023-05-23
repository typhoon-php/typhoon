<?php

declare(strict_types=1);

namespace ExtendedTypeSystem;

/**
 * @api
 * @psalm-immutable
 */
final class TypeDeclaration
{
    public function __construct(
        public readonly ?Type $nativeType = null,
        public readonly ?Type $phpDocType = null,
    ) {
    }
}
