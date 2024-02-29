<?php

namespace
{
    check(A::class);
    check(A\B::class);
    check(namespace\A::class);
    check(namespace\A\B::class);
    check(\A::class);
    check(\A\B::class);
}

namespace UnqualifiedNamespace
{
    check(A::class);
    check(A\B::class);
    check(namespace\A::class);
    check(namespace\A\B::class);
    check(\A::class);
    check(\A\B::class);
}

namespace Qualified\Namespace
{
    check(A::class);
    check(A\B::class);
    check(namespace\A::class);
    check(namespace\A\B::class);
    check(\A::class);
    check(\A\B::class);
}

namespace NamespaceWithUse
{
    use A;
    use const MY_CONST as A;
    use function myFunction as A;

    check(A::class);
    check(A\B::class);
    check(namespace\A::class);
    check(namespace\A\B::class);
    check(\A::class);
    check(\A\B::class);
}

namespace NamespaceWithQualifiedUse
{
    use C\A;
    use const MY_CONST as A;
    use function myFunction as A;

    check(A::class);
    check(A\B::class);
    check(namespace\A::class);
    check(namespace\A\B::class);
    check(\A::class);
    check(\A\B::class);
}

namespace NamespaceWithUseAndAlias
{
    use X\Y as A;
    use const MY_CONST as A;
    use function myFunction as A;

    check(A::class);
    check(A\B::class);
    check(namespace\A::class);
    check(namespace\A\B::class);
    check(\A::class);
    check(\A\B::class);
}

namespace FromClass
{
    final class A extends \ArrayObject
    {
        public function __construct()
        {
            check(self::class);
            check(parent::class);
        }
    }

    new A();

    final class B extends \IteratorIterator
    {
        public function __construct()
        {
            check(self::class);
            check(parent::class);
        }
    }

    new B();
}

// TODO anonymous classes?
