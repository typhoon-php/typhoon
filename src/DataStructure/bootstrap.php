<?php

declare(strict_types=1);

namespace Typhoon\DataStructure;

registerObjectEncoder(\stdClass::class, static fn(\stdClass $object): array => (array) $object);
registerObjectEncoder(\DateTimeInterface::class, static fn(\DateTimeInterface $object): string => $object->format('YmdHisue'));
registerObjectEncoder(KVPair::class, static fn(KVPair $object): array => [$object->key, $object->value]);
