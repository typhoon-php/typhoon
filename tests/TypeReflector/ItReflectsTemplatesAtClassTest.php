<?php

declare(strict_types=1);

namespace ExtendedTypeSystem\Reflection\TypeReflector;

use ExtendedTypeSystem\Reflection\TemplateReflection;
use ExtendedTypeSystem\Reflection\TypeReflector;
use ExtendedTypeSystem\Reflection\Variance;
use ExtendedTypeSystem\types;
use N;
use PHPUnit\Framework\Attributes\CoversNothing;

#[CoversNothing]
final class ItReflectsTemplatesAtClassTest extends TypeReflectorTestCase
{
    protected static function reflect(TypeReflector $reflector): mixed
    {
        return $reflector->reflectClassLike(N\X::class)->templates();
    }

    protected static function provide(): \Generator
    {
        yield [
            <<<'PHP'
                namespace N;
                /** 
                 * @template T 
                 */
                class X {}
                PHP,
            ['T' => new TemplateReflection(0, 'T')],
        ];
        yield [
            <<<'PHP'
                namespace N;
                /** 
                 * @template T of string 
                 */
                class X {}
                PHP,
            ['T' => new TemplateReflection(0, 'T', types::string, Variance::INVARIANT)],
        ];
        yield [
            <<<'PHP'
                namespace N;
                /** 
                 * @template-covariant T of string 
                 */
                class X {}
                PHP,
            ['T' => new TemplateReflection(0, 'T', types::string, Variance::COVARIANT)],
        ];
        yield [
            <<<'PHP'
                namespace N;
                /** 
                 * @template-contravariant T of string 
                 */
                class X {}
                PHP,
            ['T' => new TemplateReflection(0, 'T', types::string, Variance::CONTRAVARIANT)],
        ];
        yield [
            <<<'PHP'
                namespace N;
                /**
                 * @template T
                 * @template T2 of object
                 */
                class X {}
                PHP,
            [
                'T' => new TemplateReflection(0, 'T'),
                'T2' => new TemplateReflection(1, 'T2', types::object),
            ],
        ];
    }
}
