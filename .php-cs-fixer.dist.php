<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PHPyh\CodingStandard\PhpCsFixerCodingStandard;

$finder = Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->append([
        __FILE__,
        __DIR__ . '/generate-type-matcher.php',
    ]);

$config = (new Config())
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/var/.php-cs-fixer.cache');

(new PhpCsFixerCodingStandard())->applyTo($config);

$rules = $config->getRules();
$rules['ordered_class_elements']['sort_algorithm'] = 'alpha';
$rules['final_public_method_for_abstract_class'] = false;

return $config->setRules($rules);
