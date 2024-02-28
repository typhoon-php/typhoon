<?php

declare(strict_types=1);

namespace ReadonlyClasses
{
    readonly class ReadonlyClass {}

    abstract readonly class AbstractReadonlyClass {}
}

namespace TraitsWithConstants
{
    trait TraitWithConstants
    {
        const C = 1;
    }

    final class ClassUsingTraitWithConstants
    {
        use TraitWithConstants;
    }

    final class ClassAlteringConstantFromTrait
    {
        const C = 1;

        use TraitWithConstants;
    }
}
