<?php

declare(strict_types=1);

namespace Typhoon\DataStructure;

registerObjectHasher(\stdClass::class, static fn(\stdClass $object): array => (array) $object);
registerObjectHasher(\DateTimeInterface::class, static fn(\DateTimeInterface $object): string => $object->format('YmdHisue'));
registerObjectHasher(KVPair::class, static fn(KVPair $object): array => [$object->key, $object->value]);
