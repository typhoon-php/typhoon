<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PHPyh\CodingStandard\PhpCsFixerCodingStandard;

$finder = Finder::create()->append([
    __FILE__,
    __DIR__ . '/src/AttributeReflection.php',
    __DIR__ . '/src/ClassConstantReflection.php',
    __DIR__ . '/src/ClassReflection.php',
    __DIR__ . '/src/MethodReflection.php',
    __DIR__ . '/src/ParameterReflection.php',
    __DIR__ . '/src/PropertyReflection.php',
]);

$config = (new Config())
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/var/' . basename(__FILE__) . '.cache');

(new PhpCsFixerCodingStandard())->applyTo($config);

$rules = $config->getRules();
$rules['ordered_class_elements']['sort_algorithm'] = 'alpha';
$rules['no_unset_on_property'] = false;
$rules['phpdoc_no_alias_tag'] = false;

return $config->setRules($rules);
