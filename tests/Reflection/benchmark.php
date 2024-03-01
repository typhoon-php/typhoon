<?php

declare(strict_types=1);

use DragonCode\Benchmark\Benchmark;
use Typhoon\OPcache\TyphoonOPcache;
use Typhoon\Reflection\Cache\FreshCache;
use Typhoon\Reflection\Cache\NullCache;
use Typhoon\Reflection\TyphoonReflector;

require_once __DIR__ . '/../../vendor/autoload.php';

$typhoonNoCache = TyphoonReflector::build(cache: new NullCache());

$typhoonInMemoryCache = TyphoonReflector::build();

$opcache = new TyphoonOPcache(__DIR__ . '/../../var/benchmark/cache');
$opcache->clear();
$typhoonOpcache = TyphoonReflector::build(cache: $opcache);

$freshOpcache = new FreshCache(new TyphoonOPcache(__DIR__ . '/../../var/benchmark/fresh'));
$freshOpcache->clear();
$typhoonFreshOpcache = TyphoonReflector::build(cache: $freshOpcache);

// warmup class autoloading
$typhoonNoCache->reflectClass(AppendIterator::class)->getMethods();

Benchmark::start()
    ->withoutData()
    ->compare([
        'native reflection' => static fn(): array => (new ReflectionClass(AppendIterator::class))->getMethods(),
        'typhoon, no cache' => static fn(): array => $typhoonNoCache->reflectClass(AppendIterator::class)->getMethods(),
        'typhoon, in-memory cache' => static fn(): array => $typhoonInMemoryCache->reflectClass(AppendIterator::class)->getMethods(),
        'typhoon, OPcache' => static fn(): array => $typhoonOpcache->reflectClass(AppendIterator::class)->getMethods(),
        'typhoon, fresh OPcache' => static fn(): array => $typhoonFreshOpcache->reflectClass(AppendIterator::class)->getMethods(),
    ]);
