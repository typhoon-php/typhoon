<?php

declare(strict_types=1);

namespace Typhoon\Type\TemplateTest;

use Typhoon\Type\TemplateType;
use function Typhoon\Type\extractType;

/**
 * @param TemplateType<non-empty-string> $type
 */
function testStatic(TemplateType $type): void
{
    /** @psalm-check-type-exact $_type = non-empty-string */
    $_type = extractType($type);
}
