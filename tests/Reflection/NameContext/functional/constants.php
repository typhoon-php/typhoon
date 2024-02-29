<?php

namespace
{
    defineConst('A');
    defineConst('A\\B');

    check(M_PI);
    check(A);
    check(A\B);
    check(namespace\A);
    check(namespace\A\B);
    check(\A);
    check(\A\B);
}

namespace UnqualifiedNamespace
{
    defineConst('UnqualifiedNamespace\\A');
    defineConst('UnqualifiedNamespace\\A\\B');

    check(M_PI);
    check(A);
    check(A\B);
    check(namespace\A);
    check(namespace\A\B);
    check(\A);
    check(\A\B);
}

namespace Qualified\Namespace
{
    defineConst('Qualified\\Namespace\\A');
    defineConst('Qualified\\Namespace\\A\\B');

    check(A);
    check(A\B);
    check(namespace\A);
    check(namespace\A\B);
    check(\A);
    check(\A\B);
}

namespace NamespaceWithUse
{
    defineConst('NamespaceWithUse\\A');
    defineConst('NamespaceWithUse\\A\\B');

    use A;
    use function myFunction as A;

    check(A);
    check(A\B);
    check(namespace\A);
    check(namespace\A\B);
    check(\A);
    check(\A\B);
}

namespace NamespaceWithQualifiedUse
{
    defineConst('NamespaceWithQualifiedUse\A');
    defineConst('NamespaceWithQualifiedUse\A\B');
    defineConst('C\A\B');

    use C\A;
    use function myFunction as A;

    check(A);
    check(A\B);
    check(namespace\A);
    check(namespace\A\B);
    check(\A);
    check(\A\B);
}

namespace NamespaceWithQualifiedUseAndAlias
{
    defineConst('NamespaceWithQualifiedUseAndAlias\A');
    defineConst('NamespaceWithQualifiedUseAndAlias\A\B');
    defineConst('X\Y\B');

    use X\Y as A;
    use function myFunction as A;

    check(A);
    check(A\B);
    check(namespace\A);
    check(namespace\A\B);
    check(\A);
    check(\A\B);
}

namespace
{
    defineConst('self');
    defineConst('parent');

    check(self);
    check(parent);
}

namespace SelfInNamespace
{
    defineConst('SelfInNamespace\self');
    defineConst('SelfInNamespace\parent');
    defineConst('SelfInNamespace\A\static');

    check(self);
    check(parent);
    check(A\static);
}

namespace SelfInClass
{
    defineConst('SelfInClass\self');
    defineConst('SelfInClass\parent');

    final class A
    {
        public function __construct()
        {
            check(self);
            check(parent);
        }
    }

    new A;
}
