<?php

declare(strict_types=1);

namespace Classes\PHP82;

readonly class ReadonlyClass {}

abstract readonly class AbstractReadonlyClass {}

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

class ClassWith82Types
{
    public true $true;
    public false $false;
    public null $null;
}
