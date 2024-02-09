<?php

declare(strict_types=1);

use DragonCode\Benchmark\Benchmark;
use Typhoon\OPcache\TyphoonOPcache;
use Typhoon\Reflection\ClassReflection;
use Typhoon\Reflection\TyphoonReflector;

require_once __DIR__ . '/../vendor/autoload.php';

$default = TyphoonReflector::build();
$session = $default->startSession();
$cacheDev = TyphoonReflector::build(new TyphoonOPcache(__DIR__ . '/../var/benchmark/cache.dev'));
$cacheProd = TyphoonReflector::build(new TyphoonOPcache(__DIR__ . '/../var/benchmark/cache.prod'), detectChanges: false);

Benchmark::start()
    ->withoutData()
    ->compare([
        'native' => static fn(): ReflectionClass => (new ReflectionMethod(Iterator::class, 'current'))->getDeclaringClass(),
        'default' => static fn(): ClassReflection => $default->reflectClass(Iterator::class)->getMethod('current')->getDeclaringClass(),
        'session' => static fn(): ClassReflection => $session->reflectClass(Iterator::class)->getMethod('current')->getDeclaringClass(),
        'cache.dev' => static fn(): ClassReflection => $cacheDev->reflectClass(Iterator::class)->getMethod('current')->getDeclaringClass(),
        'cache.prod' => static fn(): ClassReflection => $cacheProd->reflectClass(Iterator::class)->getMethod('current')->getDeclaringClass(),
    ]);
