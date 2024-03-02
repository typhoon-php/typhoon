<?php

declare(strict_types=1);

namespace Classes\PHP83;

final class ClassWithTypedConstants
{
    public const null NULL_CONSTANT = null;
    public const false FALSE_CONSTANT = false;
    public const true TRUE_CONSTANT = true;
    public const bool BOOL_CONSTANT = true;
    public const int INT_CONSTANT = 1;
    public const float FLOAT_CONSTANT = 1.5;
    public const string STRING_CONSTANT = 'a';
}

class ClassWithDNFTypes
{
    public (\Countable&\Stringable)|false $dnf;
}
