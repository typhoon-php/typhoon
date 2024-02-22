<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PHPyh\CodingStandard\PhpCsFixerCodingStandard;

$finder = Finder::create()
    ->in(__DIR__ . '/src')
    ->notName([
        'TypeVisitor.php',
        'types.php',
    ])
    ->append([__FILE__])
    ->append(Finder::create()->in(__DIR__ . '/tests'));

$config = (new Config())
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/var/.php-cs-fixer.cache');

(new PhpCsFixerCodingStandard())->applyTo($config);

return $config;
