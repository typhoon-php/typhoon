<?php

declare(strict_types=1);

namespace Typhoon\PhpStormReflectionStubs;

use JetBrains\PHPStormStub\PhpStormStubsMap;
use Typhoon\ChangeDetector\ChangeDetector;
use Typhoon\ChangeDetector\ComposerPackageChangeDetector;
use Typhoon\ChangeDetector\FileChangeDetector;
use Typhoon\DeclarationId\ConstId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\PhpStormReflectionStubs\Internal\ApplyTentativeTypeAttribute;
use Typhoon\PhpStormReflectionStubs\Internal\CleanUp;
use Typhoon\Reflection\Internal\Data\Data;
use Typhoon\Reflection\Internal\TypedMap\TypedMap;
use Typhoon\Reflection\Locator\ConstLocator;
use Typhoon\Reflection\Locator\NamedClassLocator;
use Typhoon\Reflection\Locator\NamedFunctionLocator;
use Typhoon\Reflection\Resource;

/**
 * @api
 */
final class PhpStormStubsLocator implements ConstLocator, NamedFunctionLocator, NamedClassLocator
{
    private const PACKAGE = 'jetbrains/phpstorm-stubs';

    private static null|false|ComposerPackageChangeDetector $packageChangeDetector = false;

    private static function packageChangeDetector(): ?ChangeDetector
    {
        if (self::$packageChangeDetector === false) {
            return self::$packageChangeDetector = ComposerPackageChangeDetector::tryFromName(self::PACKAGE);
        }

        return self::$packageChangeDetector;
    }

    public function locate(ConstId|NamedFunctionId|NamedClassId $id): ?Resource
    {
        $relativePath = match (true) {
            $id instanceof ConstId => PhpStormStubsMap::CONSTANTS[$id->name] ?? null,
            $id instanceof NamedFunctionId => PhpStormStubsMap::FUNCTIONS[$id->name] ?? null,
            $id instanceof NamedClassId => PhpStormStubsMap::CLASSES[$id->name] ?? null,
        };

        if ($relativePath === null) {
            return null;
        }

        $file = PhpStormStubsMap::DIR . '/' . $relativePath;
        $code = Resource::readFile($file);

        return new Resource(
            code: $code,
            baseData: (new TypedMap())
                ->with(Data::PhpExtension, \dirname($relativePath))
                ->with(Data::InternallyDefined, true)
                ->with(Data::UnresolvedChangeDetectors, [
                    self::packageChangeDetector() ?? FileChangeDetector::fromFileAndContents($file, $code),
                ]),
            hooks: [
                new ApplyTentativeTypeAttribute(),
                new CleanUp(),
            ],
        );
    }
}
