<?php

declare(strict_types=1);

namespace Typhoon\TypeStringifier;

use Typhoon\DeclarationId\AliasId;
use Typhoon\DeclarationId\ClassId;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\TemplateId;
use Typhoon\Type\Argument;
use Typhoon\Type\ArrayElement;
use Typhoon\Type\Parameter;
use Typhoon\Type\Property;
use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;
use Typhoon\Type\Variance;
use Typhoon\Type\Visitor\DefaultTypeVisitor;

/**
 * @internal
 * @psalm-internal Typhoon\TypeStringifier
 * @implements TypeVisitor<non-empty-string>
 */
final class TypeStringifier implements TypeVisitor
{
    public function alias(Type $self, AliasId $alias, array $arguments): mixed
    {
        return $this->stringifyGenericType($alias->toString(), $arguments);
    }

    public function array(Type $self, Type $key, Type $value, array $elements): mixed
    {
        if ($elements === []) {
            return 'array' . ($this->isNever($value) ? '{}' : $this->arrayGenericPart($key, $value));
        }

        return sprintf(
            'array{%s%s}',
            implode(', ', array_map(
                function (int|string $key, ArrayElement $element) use ($elements): string {
                    /** @var ?bool */
                    static $isList = null;

                    if (!$element->optional && ($isList ??= array_is_list($elements))) {
                        return $element->type->accept($this);
                    }

                    return sprintf('%s%s: %s', $this->stringifyKey($key), $element->optional ? '?' : '', $element->type->accept($this));
                },
                array_keys($elements),
                $elements,
            )),
            $this->isNever($value) ? '' : ', ...' . $this->arrayGenericPart($key, $value),
        );
    }

    public function callable(Type $self, array $parameters, Type $return): mixed
    {
        return $this->stringifyCallable('callable', $parameters, $return);
    }

    public function classConstant(Type $self, Type $class, string $name): mixed
    {
        return sprintf('%s::%s', $class->accept($this), $name);
    }

    public function classString(Type $self, Type $class): mixed
    {
        $isObject = $class->accept(
            new /** @extends DefaultTypeVisitor<bool> */ class () extends DefaultTypeVisitor {
                public function object(Type $self, array $properties): mixed
                {
                    return true;
                }

                protected function default(Type $self): mixed
                {
                    return false;
                }
            },
        );

        if ($isObject) {
            return 'class-string';
        }

        return sprintf('class-string<%s>', $class->accept($this));
    }

    public function conditional(Type $self, Argument|Type $subject, Type $if, Type $then, Type $else): mixed
    {
        return sprintf(
            '(%s is %s ? %s : %s)',
            $subject instanceof Argument ? '$' . $subject->name : $subject->accept($this),
            $if->accept($this),
            $then->accept($this),
            $else->accept($this),
        );
    }

    public function constant(Type $self, ConstantId $constant): mixed
    {
        return sprintf('const<%s>', $constant->name);
    }

    public function float(Type $self): mixed
    {
        return 'float';
    }

    public function int(Type $self, ?int $min, ?int $max): mixed
    {
        if ($min === null && $max === null) {
            return 'int';
        }

        if ($min !== null && $max === $min) {
            return (string) $min;
        }

        return sprintf('int<%s, %s>', $min ?? 'min', $max ?? 'max');
    }

    public function intersection(Type $self, array $types): mixed
    {
        $isUnion = new /** @extends DefaultTypeVisitor<bool> */ class () extends DefaultTypeVisitor {
            public function union(Type $self, array $types): mixed
            {
                return true;
            }

            protected function default(Type $self): mixed
            {
                return false;
            }
        };

        return implode('&', array_map(
            fn(Type $type): string => $type->accept($isUnion) ? sprintf('(%s)', $type->accept($this)) : $type->accept($this),
            $types,
        ));
    }

    public function intMask(Type $self, Type $type): mixed
    {
        return sprintf('int-mask-of<%s>', $type->accept($this));
    }

    public function iterable(Type $self, Type $key, Type $value): mixed
    {
        if ($this->isMixed($key)) {
            if ($this->isMixed($value)) {
                return 'iterable';
            }

            return $this->stringifyGenericType('iterable', [$value]);
        }

        return $this->stringifyGenericType('iterable', [$key, $value]);
    }

    public function key(Type $self, Type $type): mixed
    {
        return $this->stringifyGenericType('key-of', [$type]);
    }

    public function list(Type $self, Type $value, array $elements): mixed
    {
        if ($elements === []) {
            return 'list' . ($this->isNever($value) ? '{}' : $this->arrayGenericPart(null, $value));
        }

        return sprintf(
            'list{%s%s}',
            implode(', ', array_map(
                function (int $key, ArrayElement $element) use ($elements): string {
                    /** @var ?bool */
                    static $isList = null;

                    if (!$element->optional && ($isList ??= array_is_list($elements))) {
                        return $element->type->accept($this);
                    }

                    return sprintf('%d%s: %s', $key, $element->optional ? '?' : '', $element->type->accept($this));
                },
                array_keys($elements),
                $elements,
            )),
            $this->isNever($value) ? '' : ', ...' . $this->arrayGenericPart(null, $value),
        );
    }

    public function literal(Type $self, Type $type): mixed
    {
        return 'literal-' . $type->accept($this);
    }

    public function true(Type $self): mixed
    {
        return 'true';
    }

    public function false(Type $self): mixed
    {
        return 'false';
    }

    public function floatValue(Type $self, float $value): mixed
    {
        return (string) $value;
    }

    public function stringValue(Type $self, string $value): mixed
    {
        return $this->escapeStringLiteral($value);
    }

    public function mixed(Type $self): mixed
    {
        return 'mixed';
    }

    public function namedObject(Type $self, ClassId $class, array $arguments): mixed
    {
        return $this->stringifyGenericType($class->toString(), $arguments);
    }

    public function never(Type $self): mixed
    {
        return 'never';
    }

    public function nonEmpty(Type $self, Type $type): mixed
    {
        return 'non-empty-' . $type->accept($this);
    }

    public function null(Type $self): mixed
    {
        return 'null';
    }

    public function numeric(Type $self): mixed
    {
        return 'numeric';
    }

    public function object(Type $self, array $properties): mixed
    {
        if ($properties === []) {
            return 'object';
        }

        return sprintf('object{%s}', implode(', ', array_map(
            fn(string $name, Property $property): string => sprintf(
                '%s%s: %s',
                $this->stringifyKey($name),
                $property->optional ? '?' : '',
                $property->type->accept($this),
            ),
            array_keys($properties),
            $properties,
        )));
    }

    public function offset(Type $self, Type $type, Type $offset): mixed
    {
        return sprintf('%s[%s]', $type->accept($this), $offset->accept($this));
    }

    public function resource(Type $self): mixed
    {
        return 'resource';
    }

    public function string(Type $self): mixed
    {
        return 'string';
    }

    public function self(Type $self, ?ClassId $resolvedClass, array $arguments): mixed
    {
        $name = 'self';

        if ($resolvedClass !== null) {
            $name .= '@' . $resolvedClass->name;
        }

        return $this->stringifyGenericType($name, $arguments);
    }

    public function parent(Type $self, ?NamedClassId $resolvedClass, array $arguments): mixed
    {
        $name = 'parent';

        if ($resolvedClass !== null) {
            $name .= '@' . $resolvedClass->name;
        }

        return $this->stringifyGenericType($name, $arguments);
    }

    public function static(Type $self, ?ClassId $resolvedClass, array $arguments): mixed
    {
        $name = 'static';

        if ($resolvedClass !== null) {
            $name .= '@' . $resolvedClass->name;
        }

        return $this->stringifyGenericType($name, $arguments);
    }

    public function template(Type $self, TemplateId $template): mixed
    {
        return $template->toString();
    }

    public function truthy(Type $self): mixed
    {
        return 'truthy';
    }

    public function union(Type $self, array $types): mixed
    {
        $isIntersection = new /** @extends DefaultTypeVisitor<bool> */ class () extends DefaultTypeVisitor {
            public function intersection(Type $self, array $types): mixed
            {
                return true;
            }

            protected function default(Type $self): mixed
            {
                return false;
            }
        };

        return implode('|', array_map(
            fn(Type $type): string => $type->accept($isIntersection) ? sprintf('(%s)', $type->accept($this)) : $type->accept($this),
            $types,
        ));
    }

    public function varianceAware(Type $self, Type $type, Variance $variance): mixed
    {
        return sprintf(
            '%s %s',
            match ($variance) {
                Variance::Bivariant => 'bivariant',
                Variance::Contravariant => 'contravariant',
                Variance::Covariant => 'covariant',
                Variance::Invariant => 'invariant',
            },
            $type->accept($this),
        );
    }

    public function void(Type $self): mixed
    {
        return 'void';
    }

    private function arrayGenericPart(?Type $key, Type $value): string
    {
        if ($key === null || $this->isArrayKey($key)) {
            if ($this->isMixed($value)) {
                return '';
            }

            return $this->stringifyGenericType('', [$value]);
        }

        return $this->stringifyGenericType('', [$key, $value]);
    }

    /**
     * @return non-empty-string
     */
    private function escapeStringLiteral(string $literal): string
    {
        /** @var non-empty-string */
        return str_replace("\n", '\n', var_export($literal, return: true));
    }

    private function isArrayKey(Type $self): bool
    {
        return $self->accept(
            new /** @extends DefaultTypeVisitor<int> */ class () extends DefaultTypeVisitor {
                protected function default(Type $self): mixed
                {
                    return 0b100;
                }

                public function int(Type $self, ?int $min, ?int $max): mixed
                {
                    if ($min === null && $max === null) {
                        return 0b001;
                    }

                    return 0b100;
                }

                public function string(Type $self): mixed
                {
                    return 0b010;
                }

                public function union(Type $self, array $types): mixed
                {
                    $value = 0;

                    foreach ($types as $inner) {
                        $value |= $inner->accept($this);
                    }

                    return $value;
                }
            },
        ) === 0b11;
    }

    private function isNever(Type $type): bool
    {
        return $type->accept(
            new /** @extends DefaultTypeVisitor<bool> */ class () extends DefaultTypeVisitor {
                protected function default(Type $self): mixed
                {
                    return false;
                }

                public function never(Type $self): mixed
                {
                    return true;
                }
            },
        );
    }

    private function isMixed(Type $type): bool
    {
        return $type->accept(
            new /** @extends DefaultTypeVisitor<bool> */ class () extends DefaultTypeVisitor {
                protected function default(Type $self): mixed
                {
                    return false;
                }

                public function mixed(Type $self): mixed
                {
                    return true;
                }
            },
        );
    }

    /**
     * @param non-empty-string $name
     * @param list<Parameter> $parameters
     * @return non-empty-string
     */
    private function stringifyCallable(string $name, array $parameters, Type $return): string
    {
        if ($parameters === [] && $this->isMixed($return)) {
            return $name;
        }

        return sprintf(
            '%s(%s): %s',
            $name,
            implode(', ', array_map(
                fn(Parameter $parameter): string => $parameter->type->accept($this) . match (true) {
                    $parameter->variadic => '...',
                    $parameter->hasDefault => '=',
                    default => '',
                },
                $parameters,
            )),
            $return->accept($this),
        );
    }

    /**
     * @param list<Type> $arguments
     * @return ($name is non-empty-string ? non-empty-string : string)
     */
    private function stringifyGenericType(string $name, array $arguments): string
    {
        if ($arguments === []) {
            return $name;
        }

        return sprintf('%s<%s>', $name, implode(', ', array_map(
            fn(Type $self): string => $self->accept($this),
            $arguments,
        )));
    }

    /**
     * @return non-empty-string
     */
    private function stringifyKey(int|string $key): string
    {
        if (\is_int($key)) {
            return (string) $key;
        }

        if ($key === '' || preg_match('/\W/', $key)) {
            return $this->escapeStringLiteral($key);
        }

        return $key;
    }
}
