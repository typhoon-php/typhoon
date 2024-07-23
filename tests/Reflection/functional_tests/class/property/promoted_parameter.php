<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Locator\Resource;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNull;

return static function (TyphoonReflector $reflector): void {
    $class = $reflector
        ->withResource(Resource::fromCode(
            <<<'PHP'
                <?php
                final class A
                {
                    public string $nonPromotedProp;
                
                    public function __construct(
                        public string $promotedProp,
                        string $nonPromotedParam,
                    ) {}
                }
                PHP,
        ))
        ->reflectClass('A');
    $properties = $class->properties();
    $parameters = $class->methods()['__construct']->parameters();

    assertEquals($parameters['promotedProp'], $properties['promotedProp']->promotedParameter());
    assertEquals($properties['promotedProp'], $parameters['promotedProp']->promotedProperty());
    assertNull($properties['nonPromotedProp']->promotedParameter());
    assertNull($parameters['nonPromotedParam']->promotedProperty());
};
