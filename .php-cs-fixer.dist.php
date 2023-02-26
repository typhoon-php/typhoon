<?php

declare(strict_types=1);

use PHPyh\CodingStandard\PhpCsFixerCodingStandard;

require_once __DIR__.'/vendor/autoload.php';

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->append([
        __FILE__,
    ])
;

$config = (new PhpCsFixer\Config())
    ->setFinder($finder)
    ->setCacheFile(__DIR__.'/var/.php-cs-fixer.cache')
;

(new PhpCsFixerCodingStandard())->applyTo($config);

return $config;
