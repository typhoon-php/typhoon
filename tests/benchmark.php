<?php

declare(strict_types=1);

use DragonCode\Benchmark\Benchmark;
use Typhoon\OPcache\TyphoonOPcache;
use Typhoon\Reflection\Cache\InMemoryCache;
use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\TyphoonReflector;

require_once __DIR__ . '/../vendor/autoload.php';

$noCache = TyphoonReflector::build();
$inMemoryCache = TyphoonReflector::build(new InMemoryCache());
$fileCache = TyphoonReflector::build(new TyphoonOPcache(__DIR__ . '/../var/benchmark/cached'));

Benchmark::start()
    ->withoutData()
    ->compare([
        'native reflection' => static fn(): ReflectionClass => (new ReflectionMethod(Iterator::class, 'current'))->getDeclaringClass(),
        'typhoon w/o cache' => static fn(): ClassReflection => $noCache->reflectClass(Iterator::class)->getMethod('current')->getDeclaringClass(),
        'typhoon with in-memory cache' => static fn(): ClassReflection => $inMemoryCache->reflectClass(Iterator::class)->getMethod('current')->getDeclaringClass(),
        'typhoon with file cache' => static fn(): ClassReflection => $fileCache->reflectClass(Iterator::class)->getMethod('current')->getDeclaringClass(),
    ]);
