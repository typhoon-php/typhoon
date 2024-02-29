<?php

declare(strict_types=1);

use PhpCsFixer\Finder;

$config = (require_once __DIR__ . '/.php-cs-fixer.dist.php')
    ->setFinder(Finder::create()->append([
        __FILE__,
        __DIR__ . '/src/Reflection/AttributeReflection.php',
        __DIR__ . '/src/Reflection/ClassConstantReflection.php',
        __DIR__ . '/src/Reflection/ClassReflection.php',
        __DIR__ . '/src/Reflection/MethodReflection.php',
        __DIR__ . '/src/Reflection/ParameterReflection.php',
        __DIR__ . '/src/Reflection/PropertyReflection.php',
        __DIR__ . '/src/Reflection/TypeResolver/RecursiveTypeReplacer.php',
        __DIR__ . '/src/Reflection/TypeResolver/TemplateResolver.php',
        __DIR__ . '/src/Type/DefaultTypeVisitor.php',
        __DIR__ . '/src/Type/types.php',
        __DIR__ . '/src/Type/TypeVisitor.php',
    ]))
    ->setCacheFile(__DIR__ . '/var/' . basename(__FILE__) . '.cache');

$rules = $config->getRules();
$rules['ordered_class_elements']['sort_algorithm'] = 'alpha';

return $config->setRules($rules);
