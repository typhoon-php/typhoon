<?php

declare(strict_types=1);

namespace Typhoon\Reflection;

use Typhoon\ChangeDetector\FileChangeDetector;
use Typhoon\Reflection\Exception\FileIsNotReadable;
use Typhoon\Reflection\Internal\Data\Data;
use Typhoon\Reflection\Internal\ReflectionHook\ClassReflectionHook;
use Typhoon\Reflection\Internal\ReflectionHook\ConstReflectionHook;
use Typhoon\Reflection\Internal\ReflectionHook\FunctionReflectionHook;
use Typhoon\Reflection\Internal\ReflectionHook\ReflectionHooks;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;

/**
 * @api
 */
final class Resource
{
    public readonly ReflectionHooks $hooks;

    /**
     * @param list<ConstReflectionHook|FunctionReflectionHook|ClassReflectionHook> $hooks
     */
    public function __construct(
        public readonly string $code,
        public readonly TypedMap $baseData = new TypedMap(),
        array $hooks = [],
    ) {
        $this->hooks = new ReflectionHooks($hooks);
    }

    /**
     * @param list<ConstReflectionHook|FunctionReflectionHook|ClassReflectionHook> $hooks
     */
    public static function fromFile(string $file, TypedMap $baseData = new TypedMap(), array $hooks = []): self
    {
        $code = self::readFile($file);

        return new self(
            code: $code,
            baseData: $baseData
                ->with(Data::File, $file)
                ->with(Data::UnresolvedChangeDetectors, [FileChangeDetector::fromFileAndContents($file, $code)]),
            hooks: $hooks,
        );
    }

    /**
     * @psalm-assert non-empty-string $file
     * @phpstan-assert non-empty-string $file
     * @throws FileIsNotReadable
     */
    public static function readFile(string $file): string
    {
        $contents = @file_get_contents($file);

        if ($contents === false) {
            throw new FileIsNotReadable($file);
        }

        return $contents;
    }
}
