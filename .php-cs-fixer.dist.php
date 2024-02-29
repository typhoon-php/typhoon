<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PHPyh\CodingStandard\PhpCsFixerCodingStandard;

$config = (new Config())
    ->setFinder(
        Finder::create()
            ->in(__DIR__ . '/src')
            ->in(__DIR__ . '/dev')
            ->append([__FILE__])
            ->append(
                Finder::create()
                    ->in(__DIR__ . '/tests')
                    ->exclude([
                        'Reflection/NameContext/functional',
                        'Reflection/Fixtures',
                    ]),
            ),
    )
    ->setCacheFile(__DIR__ . '/var/.php-cs-fixer.cache');

(new PhpCsFixerCodingStandard())->applyTo($config, [
    'final_public_method_for_abstract_class' => false,
    'no_unset_on_property' => false,
    'strict_comparison' => false,
]);

return $config;
