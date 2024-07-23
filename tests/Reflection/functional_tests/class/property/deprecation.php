<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\Reflection\Locator\Resource;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertTrue;

return static function (TyphoonReflector $reflector): void {
    $properties = $reflector
        ->withResource(Resource::fromCode(
            <<<'PHP'
                <?php
                final class A
                {
                    public $notDeprecated;
                    /** @deprecated */
                    public $deprecated;
                    /** @deprecated Message */
                    public $deprecatedWithMessage;
                    
                    public function __construct(
                        public $promotedNotDeprecated,
                        /** @deprecated */
                        public $promotedDeprecated,
                        /** @deprecated Message */
                        public $promotedDeprecatedWithMessage,
                    ) {}
                }
                PHP,
        ))
        ->reflectClass('A')
        ->properties();

    assertFalse($properties['notDeprecated']->isDeprecated());
    assertNull($properties['notDeprecated']->deprecation());
    assertTrue($properties['deprecated']->isDeprecated());
    assertEquals(new Deprecation(), $properties['deprecated']->deprecation());
    assertTrue($properties['deprecatedWithMessage']->isDeprecated());
    assertEquals(new Deprecation('Message'), $properties['deprecatedWithMessage']->deprecation());

    assertFalse($properties['promotedNotDeprecated']->isDeprecated());
    assertNull($properties['promotedNotDeprecated']->deprecation());
    assertTrue($properties['promotedDeprecated']->isDeprecated());
    assertEquals(new Deprecation(), $properties['promotedDeprecated']->deprecation());
    assertTrue($properties['promotedDeprecatedWithMessage']->isDeprecated());
    assertEquals(new Deprecation('Message'), $properties['promotedDeprecatedWithMessage']->deprecation());
};
