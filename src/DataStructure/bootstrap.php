<?php

declare(strict_types=1);

namespace Typhoon\DataStructure;

registerObjectEncoder(
    classes: [\stdClass::class],
    encoder: static fn(\stdClass $object): array => (array) $object,
);
registerObjectEncoder(
    classes: [\DateTimeInterface::class],
    encoder: static fn(\DateTimeInterface $object): string => $object->format('YmdHisue'),
);
registerObjectEncoder(
    classes: [KVPair::class],
    encoder: static fn(KVPair $object): array => [$object->key, $object->value],
);
