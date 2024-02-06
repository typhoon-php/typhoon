<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PHPyh\CodingStandard\PhpCsFixerCodingStandard;

$finder = Finder::create()
    ->in(__DIR__ . '/src')
    ->notName([
        'AttributeReflection.php',
        'ClassReflection.php',
        'MethodReflection.php',
        'ParameterReflection.php',
        'PropertyReflection.php',
        'RecursiveTypeReplacer.php',
        'TypeInheritanceResolver.php',
    ])
    ->append([__FILE__])
    ->append(
        Finder::create()
            ->in(__DIR__ . '/tests')
            ->exclude([
                'unit/NameContext/functional',
                'unit/ReflectorCompatibility',
            ]),
    );

$config = (new Config())
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/var/.php-cs-fixer.cache');

(new PhpCsFixerCodingStandard())->applyTo($config);

return $config;
