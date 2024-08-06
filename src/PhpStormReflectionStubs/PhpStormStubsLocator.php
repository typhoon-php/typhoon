<?php

declare(strict_types=1);

namespace Typhoon\PhpStormReflectionStubs;

use JetBrains\PHPStormStub\PhpStormStubsMap;
use Typhoon\ChangeDetector\ChangeDetector;
use Typhoon\ChangeDetector\ComposerPackageChangeDetector;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\PhpStormReflectionStubs\Internal\ApplyLanguageLevelTypeAwareAttribute;
use Typhoon\PhpStormReflectionStubs\Internal\ApplyTentativeTypeAttribute;
use Typhoon\PhpStormReflectionStubs\Internal\RemovePhpStormMetaAttributes;
use Typhoon\PhpStormReflectionStubs\Internal\RemoveThrowableToString;
use Typhoon\PhpStormReflectionStubs\Internal\RemoveTraversableExtendsIterable;
use Typhoon\Reflection\Exception\FileIsNotReadable;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Locator\ConstantLocator;
use Typhoon\Reflection\Locator\NamedClassLocator;
use Typhoon\Reflection\Locator\NamedFunctionLocator;
use Typhoon\Reflection\Locator\Resource;
use Typhoon\TypedMap\TypedMap;

/**
 * @api
 */
final class PhpStormStubsLocator implements ConstantLocator, NamedFunctionLocator, NamedClassLocator
{
    private const PACKAGE = 'jetbrains/phpstorm-stubs';

    private static ?ComposerPackageChangeDetector $changeDetector = null;

    private static function changeDetector(): ChangeDetector
    {
        return self::$changeDetector ??= ComposerPackageChangeDetector::fromName(self::PACKAGE);
    }

    public function locate(ConstantId|NamedFunctionId|NamedClassId $id): ?Resource
    {
        $relativePath = match (true) {
            $id instanceof ConstantId => defined($id->name) ? null : PhpStormStubsMap::CONSTANTS[$id->name] ?? null,
            $id instanceof NamedFunctionId => PhpStormStubsMap::FUNCTIONS[$id->name] ?? null,
            $id instanceof NamedClassId => PhpStormStubsMap::CLASSES[$id->name] ?? null,
        };

        if ($relativePath === null) {
            return null;
        }

        $file = PhpStormStubsMap::DIR . '/' . $relativePath;
        $code = @file_get_contents($file);

        if ($code === false) {
            throw new FileIsNotReadable($file);
        }

        return Resource::fromCode(
            code: $code,
            data: (new TypedMap())
                ->with(Data::PhpExtension, \dirname($relativePath))
                ->with(Data::InternallyDefined, true)
                ->with(Data::ChangeDetector, self::changeDetector()),
            hooks: [
                RemoveThrowableToString::Instance,
                RemoveTraversableExtendsIterable::Instance,
                ApplyLanguageLevelTypeAwareAttribute::Instance,
                ApplyTentativeTypeAttribute::Instance,
                RemovePhpStormMetaAttributes::Instance,
            ],
        );
    }
}
