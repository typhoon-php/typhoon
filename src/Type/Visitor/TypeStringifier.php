<?php

declare(strict_types=1);

namespace Typhoon\Type\Visitor;

use Typhoon\DeclarationId\AliasId;
use Typhoon\DeclarationId\AnonymousClassId;
use Typhoon\DeclarationId\AnonymousFunctionId;
use Typhoon\DeclarationId\ConstantId;
use Typhoon\DeclarationId\MethodId;
use Typhoon\DeclarationId\NamedClassId;
use Typhoon\DeclarationId\NamedFunctionId;
use Typhoon\DeclarationId\ParameterId;
use Typhoon\DeclarationId\TemplateId;
use Typhoon\Type\Parameter;
use Typhoon\Type\ShapeElement;
use Typhoon\Type\Type;
use Typhoon\Type\TypeVisitor;
use Typhoon\Type\Variance;

/**
 * @internal
 * @psalm-internal Typhoon\Type
 * @implements TypeVisitor<non-empty-string>
 */
enum TypeStringifier implements TypeVisitor
{
    case Instance;

    public function never(Type $type): mixed
    {
        return 'never';
    }

    public function void(Type $type): mixed
    {
        return 'void';
    }

    public function null(Type $type): mixed
    {
        return 'null';
    }

    public function true(Type $type): mixed
    {
        return 'true';
    }

    public function false(Type $type): mixed
    {
        return 'false';
    }

    public function int(Type $type, Type $minType, Type $maxType): mixed
    {
        $string = \sprintf('int<%s, %s>', $minType->accept($this), $maxType->accept($this));
        /** @var non-empty-string */
        $string = strtr($string, ['constant<PHP_INT_MIN>' => 'min', 'constant<PHP_INT_MAX>' => 'max']);

        if ($string === 'int<min, max>') {
            return 'int';
        }

        return $string;
    }

    public function intValue(Type $type, int $value): mixed
    {
        return (string) $value;
    }

    public function intMask(Type $type, Type $ofType): mixed
    {
        return \sprintf('int-mask-of<%s>', $ofType->accept($this));
    }

    public function float(Type $type, Type $minType, Type $maxType): mixed
    {
        $string = \sprintf('float<%s, %s>', $minType->accept($this), $maxType->accept($this));
        /** @var non-empty-string */
        $string = strtr($string, ['constant<PHP_FLOAT_MIN>' => 'min', 'constant<PHP_FLOAT_MAX>' => 'max']);

        if ($string === 'float<min, max>') {
            return 'float';
        }

        return $string;
    }

    public function floatValue(Type $type, float $value): mixed
    {
        return (string) $value;
    }

    public function string(Type $type): mixed
    {
        return 'string';
    }

    public function stringValue(Type $type, string $value): mixed
    {
        return $this->escapeStringLiteral($value);
    }

    public function classString(Type $type, Type $classType): mixed
    {
        $isObject = $classType->accept(
            new /** @extends DefaultTypeVisitor<bool> */ class () extends DefaultTypeVisitor {
                public function object(Type $type, array $properties): mixed
                {
                    return true;
                }

                protected function default(Type $type): mixed
                {
                    return false;
                }
            },
        );

        if ($isObject) {
            return 'class-string';
        }

        return \sprintf('class-string<%s>', $classType->accept($this));
    }

    public function numeric(Type $type): mixed
    {
        return 'numeric';
    }

    public function literal(Type $type, Type $ofType): mixed
    {
        return 'literal-' . $ofType->accept($this);
    }

    public function resource(Type $type): mixed
    {
        return 'resource';
    }

    public function list(Type $type, Type $valueType, array $elements): mixed
    {
        $value = $valueType->accept($this);

        if ($elements === []) {
            return 'list' . match ($value) {
                'never' => '{}',
                'mixed' => '',
                default => "<{$value}>",
            };
        }

        return \sprintf(
            'list{%s%s}',
            implode(', ', array_map(
                function (int $key, ShapeElement $element) use ($elements): string {
                    /** @var ?bool */
                    static $isList = null;

                    if (!$element->optional && ($isList ??= array_is_list($elements))) {
                        return $element->type->accept($this);
                    }

                    return \sprintf('%d%s: %s', $key, $element->optional ? '?' : '', $element->type->accept($this));
                },
                array_keys($elements),
                $elements,
            )),
            match ($value) {
                'never' => '',
                'mixed' => ', ...',
                default => ", ...<{$value}>",
            },
        );
    }

    public function array(Type $type, Type $keyType, Type $valueType, array $elements): mixed
    {
        $key = $keyType->accept($this);
        $value = $valueType->accept($this);

        if ($elements === []) {
            return 'array' . match ($value) {
                'never' => '{}',
                'mixed' => $key === 'int|string' ? '' : "<{$key}, mixed>",
                default => $key === 'int|string' ? "<{$value}>" : "<{$key}, {$value}>",
            };
        }

        return \sprintf(
            'array{%s%s}',
            implode(', ', array_map(
                function (int|string $key, ShapeElement $element) use ($elements): string {
                    /** @var ?bool */
                    static $isList = null;

                    if (!$element->optional && ($isList ??= array_is_list($elements))) {
                        return $element->type->accept($this);
                    }

                    return \sprintf('%s%s: %s', $this->stringifyKey($key), $element->optional ? '?' : '', $element->type->accept($this));
                },
                array_keys($elements),
                $elements,
            )),
            match ($value) {
                'never' => '',
                'mixed' => $key === 'int|string' ? ', ...' : ", ...<{$key}, mixed>",
                default => $key === 'int|string' ? ", ...<{$value}>" : ", ...<{$key}, {$value}>",
            },
        );
    }

    public function key(Type $type, Type $arrayType): mixed
    {
        return $this->stringifyGenericType('key-of', [$arrayType]);
    }

    public function offset(Type $type, Type $arrayType, Type $keyType): mixed
    {
        return \sprintf('%s[%s]', $arrayType->accept($this), $keyType->accept($this));
    }

    public function iterable(Type $type, Type $keyType, Type $valueType): mixed
    {
        $key = $keyType->accept($this);
        $value = $valueType->accept($this);

        if ($key === 'mixed') {
            if ($value === 'mixed') {
                return 'iterable';
            }

            return "iterable<{$value}>";
        }

        return "iterable<{$key}, {$value}>";
    }

    public function object(Type $type, array $properties): mixed
    {
        if ($properties === []) {
            return 'object';
        }

        return \sprintf('object{%s}', implode(', ', array_map(
            fn(string $name, ShapeElement $property): string => \sprintf(
                '%s%s: %s',
                $this->stringifyKey($name),
                $property->optional ? '?' : '',
                $property->type->accept($this),
            ),
            array_keys($properties),
            $properties,
        )));
    }

    public function namedObject(Type $type, NamedClassId $classId, array $typeArguments): mixed
    {
        return $this->stringifyGenericType($classId->name, $typeArguments);
    }

    public function self(Type $type, array $typeArguments, null|NamedClassId|AnonymousClassId $resolvedClassId): mixed
    {
        $name = 'self';

        if ($resolvedClassId !== null) {
            $name .= '@' . $this->stringifyId($resolvedClassId);
        }

        return $this->stringifyGenericType($name, $typeArguments);
    }

    public function parent(Type $type, array $typeArguments, ?NamedClassId $resolvedClassId): mixed
    {
        $name = 'parent';

        if ($resolvedClassId !== null) {
            $name .= '@' . $resolvedClassId->name;
        }

        return $this->stringifyGenericType($name, $typeArguments);
    }

    public function static(Type $type, array $typeArguments, null|NamedClassId|AnonymousClassId $resolvedClassId): mixed
    {
        $name = 'static';

        if ($resolvedClassId !== null) {
            $name .= '@' . $this->stringifyId($resolvedClassId);
        }

        return $this->stringifyGenericType($name, $typeArguments);
    }

    public function callable(Type $type, array $parameters, Type $returnType): mixed
    {
        $returnString = $returnType->accept($this);

        if ($parameters === [] && $returnString === 'mixed') {
            return 'callable';
        }

        return \sprintf(
            'callable(%s): %s',
            implode(', ', array_map(
                fn(Parameter $parameter): string => $parameter->type->accept($this) . match (true) {
                    $parameter->variadic => '...',
                    $parameter->hasDefault => '=',
                    default => '',
                },
                $parameters,
            )),
            $returnString,
        );
    }

    public function constant(Type $type, ConstantId $constantId): mixed
    {
        return \sprintf('constant<%s>', $constantId->name);
    }

    public function classConstant(Type $type, Type $classType, string $name): mixed
    {
        return \sprintf('%s::%s', $classType->accept($this), $name);
    }

    public function classConstantMask(Type $type, Type $classType, string $namePrefix): mixed
    {
        return \sprintf('%s::%s*', $classType->accept($this), $namePrefix);
    }

    public function alias(Type $type, AliasId $aliasId, array $typeArguments): mixed
    {
        return \sprintf('%s@%s', $aliasId->name, $this->stringifyId($aliasId->class));
    }

    public function template(Type $type, TemplateId $templateId): mixed
    {
        return \sprintf('%s#%s', $templateId->name, $this->stringifyId($templateId->declaration));
    }

    public function varianceAware(Type $type, Type $ofType, Variance $variance): mixed
    {
        return \sprintf(
            '%s %s',
            match ($variance) {
                Variance::Bivariant => 'bivariant',
                Variance::Contravariant => 'contravariant',
                Variance::Covariant => 'covariant',
                Variance::Invariant => 'invariant',
            },
            $ofType->accept($this),
        );
    }

    public function union(Type $type, array $ofTypes): mixed
    {
        $isIntersection = new /** @extends DefaultTypeVisitor<bool> */ class () extends DefaultTypeVisitor {
            public function intersection(Type $type, array $ofTypes): mixed
            {
                return true;
            }

            protected function default(Type $type): mixed
            {
                return false;
            }
        };

        $string = implode('|', array_map(
            fn(Type $type): string => $type->accept($isIntersection) ? \sprintf('(%s)', $type->accept($this)) : $type->accept($this),
            $ofTypes,
        ));

        /** @var non-empty-string */
        return strtr($string, [
            'true|false' => 'bool',
            'bool|int|float|string' => 'scalar',
        ]);
    }

    public function conditional(Type $type, Type $subjectType, Type $ifType, Type $thenType, Type $elseType): mixed
    {
        return \sprintf('(%s is %s ? %s : %s)', $subjectType->accept($this), $ifType->accept($this), $thenType->accept($this), $elseType->accept($this));
    }

    public function argument(Type $type, ParameterId $parameterId): mixed
    {
        return '$' . $parameterId->name;
    }

    public function intersection(Type $type, array $ofTypes): mixed
    {
        $isUnion = new /** @extends DefaultTypeVisitor<bool> */ class () extends DefaultTypeVisitor {
            public function union(Type $type, array $ofTypes): mixed
            {
                return true;
            }

            protected function default(Type $type): mixed
            {
                return false;
            }
        };

        $string = implode('&', array_map(
            fn(Type $type): string => $type->accept($isUnion) ? \sprintf('(%s)', $type->accept($this)) : $type->accept($this),
            $ofTypes,
        ));

        /** @var non-empty-string */
        return strtr($string, [
            'Closure&callable' => 'Closure',
            "string&!''&!'0'" => 'truthy-string',
            "string&!''" => 'non-empty-string',
            'string&numeric' => 'numeric-string',
            '!array{}&array' => 'non-empty-array',
        ]);
    }

    public function not(Type $type, Type $ofType): mixed
    {
        return '!' . $ofType->accept($this);
    }

    public function mixed(Type $type): mixed
    {
        return 'mixed';
    }

    /**
     * @return non-empty-string
     */
    private function stringifyId(NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId|MethodId $id): string
    {
        return match (true) {
            $id instanceof NamedFunctionId => $id->name . '()',
            $id instanceof AnonymousFunctionId => \sprintf('anonymous:%s:%d%s()', $id->file, $id->line, $id->column === null ? '' : ':' . $id->column),
            $id instanceof NamedClassId => $id->name,
            $id instanceof AnonymousClassId => \sprintf('anonymous:%s:%d%s', $id->file, $id->line, $id->column === null ? '' : ':' . $id->column),
            $id instanceof MethodId => \sprintf('%s::%s()', $this->stringifyId($id->class), $id->name),
        };
    }

    /**
     * @return non-empty-string
     */
    private function escapeStringLiteral(string $literal): string
    {
        /** @var non-empty-string */
        return str_replace("\n", '\n', var_export($literal, return: true));
    }

    /**
     * @param non-empty-string $name
     * @param list<Type> $arguments
     * @return non-empty-string
     */
    private function stringifyGenericType(string $name, array $arguments): string
    {
        if ($arguments === []) {
            return $name;
        }

        return \sprintf('%s<%s>', $name, implode(', ', array_map(
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
