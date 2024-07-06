<?php

declare(strict_types=1);

namespace Typhoon\Reflection\FunctionalTesting;

use Typhoon\Reflection\Kind;
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\Type\types;
use function PHPUnit\Framework\assertEquals;
use function Typhoon\DeclarationId\namedClassId;

return static function (TyphoonReflector $reflector): void {
    $reflection = $reflector->reflectCode(
        <<<'PHP'
            <?php
            
            abstract class A
            {
                /**
                 * @return 'a'
                 */
                public function a(): string {}
            }
            
            class B extends A
            {
            }
            PHP,
    )[namedClassId('B')]->methods['a'];

    assertEquals(types::string, $reflection->returnType(Kind::Native));
    assertEquals(types::stringValue('a'), $reflection->returnType(Kind::Annotated));
    assertEquals(types::stringValue('a'), $reflection->returnType(Kind::Resolved));
};
