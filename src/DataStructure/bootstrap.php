<?php

declare(strict_types=1);

namespace Typhoon\DataStructure;

registerObjectEncoder(
    classes: [\DateTimeInterface::class],
    encoder: static fn(\DateTimeInterface $date): string => $date->format('YmdHisue'),
    prefix: 'd',
);
