<?php

declare(strict_types=1);

namespace Typhoon\DataStructure;

ObjectNormalizers::register(
    \DateTimeInterface::class,
    static fn(\DateTimeInterface $date): string => $date->format('YmdHisue'),
);
