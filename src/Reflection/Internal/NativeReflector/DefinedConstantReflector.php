<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Internal\NativeReflector;

use Typhoon\ChangeDetector\ConstantChangeDetector;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\Reflection\Internal\ConstantExpression\Value;
use Typhoon\Reflection\Internal\Data;
use Typhoon\Reflection\Internal\Data\TypeData;
use Typhoon\Type\types;
use Typhoon\TypedMap\TypedMap;
use function Typhoon\Reflection\Internal\get_namespace;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class DefinedConstantReflector
{
    public function reflectConstant(ConstantId $id): ?TypedMap
    {
        if (!\defined($id->name)) {
            return null;
        }

        $value = \constant($id->name);
        $extension = $this->constantExtensions()[$id->name] ?? null;

        return (new TypedMap())
            ->with(Data::Type, new TypeData(inferred: types::value($value)))
            ->with(Data::Namespace, get_namespace($id->name))
            ->with(Data::ValueExpression, Value::from($value))
            ->with(Data::PhpExtension, $extension)
            ->with(Data::ChangeDetector, new ConstantChangeDetector(name: $id->name, exists: true, value: $value))
            ->with(Data::InternallyDefined, $extension !== null);
    }

    /**
     * @var ?array<non-empty-string, non-empty-string>
     */
    private ?array $constantExtensions = null;

    /**
     * @return array<non-empty-string, non-empty-string>
     */
    private function constantExtensions(): array
    {
        if ($this->constantExtensions !== null) {
            return $this->constantExtensions;
        }

        $this->constantExtensions = [];

        foreach (get_defined_constants(categorize: true) as $category => $constants) {
            if ($category === 'user') {
                continue;
            }

            foreach ($constants as $name => $_value) {
                /**
                 * @var non-empty-string $name
                 * @var non-empty-string $category
                 */
                $this->constantExtensions[$name] = $category;
            }
        }

        return $this->constantExtensions;
    }
}
