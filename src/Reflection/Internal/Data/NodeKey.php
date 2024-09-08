<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\Data;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Const_;
use PhpParser\Node\Stmt\Function_;
use Typhoon\TypedMap\OptionalKey;
use Typhoon\TypedMap\TypedMap;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection\Internal
 * @implements OptionalKey<null|ClassLike|Function_|FuncCall|Const_>
 */
enum NodeKey implements OptionalKey
{
    case Key;

    public function default(TypedMap $map): mixed
    {
        return null;
    }
}
