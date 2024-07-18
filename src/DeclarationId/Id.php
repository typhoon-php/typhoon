<?php

declare(strict_types=1);

namespace Typhoon\DeclarationId;

/**
 * @api
 */
abstract class Id implements \JsonSerializable
{
    private const ANONYMOUS_CLOSURE_NAME = '{closure}';
    protected const CODE_CONST = 'c';
    protected const CODE_NAMED_FUNCTION = 'nf';
    protected const CODE_ANONYMOUS_FUNCTION = 'af';
    protected const CODE_NAMED_CLASS = 'nc';
    protected const CODE_ANONYMOUS_CLASS = 'ac';
    protected const CODE_ALIAS = 'a';
    protected const CODE_TEMPLATE = 't';
    protected const CODE_CLASS_CONST = 'cc';
    protected const CODE_PROPERTY = 'p';
    protected const CODE_METHOD = 'm';
    protected const CODE_PARAMETER = 'pa';

    /**
     * @param non-empty-string $name
     */
    final public static function const(string $name): ConstId
    {
        return new ConstId($name);
    }

    /**
     * @template TName of non-empty-string
     * @param TName $name
     * @return NamedFunctionId<TName>
     */
    final public static function namedFunction(string $name): NamedFunctionId
    {
        return new NamedFunctionId($name);
    }

    /**
     * @param non-empty-string $file
     * @param positive-int $line
     * @param ?positive-int $column
     */
    final public static function anonymousFunction(string $file, int $line, ?int $column = null): AnonymousFunctionId
    {
        return new AnonymousFunctionId($file, $line, $column);
    }

    /**
     * @template TName of non-empty-string
     * @param TName $name
     * @return NamedClassId<TName>|(TName is class-string ? AnonymousClassId<TName> : AnonymousClassId<null>)
     * @psalm-suppress InvalidReturnType, InvalidReturnStatement
     */
    final public static function class(string $name): NamedClassId|AnonymousClassId
    {
        if (str_contains($name, '@')) {
            return AnonymousClassId::fromName($name);
        }

        return new NamedClassId($name);
    }

    /**
     * @template TName of non-empty-string
     * @param TName $name
     * @return NamedClassId<TName>
     */
    final public static function namedClass(string $name): NamedClassId
    {
        if (str_contains($name, '@')) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot create NamedClassId from anonymous class name %s',
                AnonymousClassId::normalizeClassNameForException($name),
            ));
        }

        return new NamedClassId($name);
    }

    /**
     * @param non-empty-string $file
     * @param positive-int $line
     * @param ?positive-int $column
     * @return AnonymousClassId<null>
     */
    final public static function anonymousClass(string $file, int $line, ?int $column = null): AnonymousClassId
    {
        return new AnonymousClassId($file, $line, $column);
    }

    /**
     * @param non-empty-string|NamedClassId|AnonymousClassId $class
     * @param non-empty-string $name
     */
    final public static function classConst(string|NamedClassId|AnonymousClassId $class, string $name): ClassConstId
    {
        if (\is_string($class)) {
            $class = self::class($class);
        }

        return new ClassConstId($class, $name);
    }

    /**
     * @param non-empty-string|NamedClassId|AnonymousClassId $class
     * @param non-empty-string $name
     */
    final public static function property(string|NamedClassId|AnonymousClassId $class, string $name): PropertyId
    {
        if (\is_string($class)) {
            $class = self::class($class);
        }

        return new PropertyId($class, $name);
    }

    /**
     * @param non-empty-string|NamedClassId|AnonymousClassId $class
     * @param non-empty-string $name
     */
    final public static function method(string|NamedClassId|AnonymousClassId $class, string $name): MethodId
    {
        if (\is_string($class)) {
            $class = self::class($class);
        }

        return new MethodId($class, $name);
    }

    /**
     * @param non-empty-string $name
     */
    final public static function parameter(NamedFunctionId|AnonymousFunctionId|MethodId $function, string $name): ParameterId
    {
        return new ParameterId($function, $name);
    }

    /**
     * @param non-empty-string|NamedClassId|AnonymousClassId $class
     * @param non-empty-string $name
     */
    final public static function alias(string|NamedClassId|AnonymousClassId $class, string $name): AliasId
    {
        if (\is_string($class)) {
            $class = self::class($class);
        }

        return new AliasId($class, $name);
    }

    /**
     * @param non-empty-string $name
     */
    final public static function template(NamedFunctionId|AnonymousFunctionId|NamedClassId|AnonymousClassId|MethodId $site, string $name): TemplateId
    {
        return new TemplateId($site, $name);
    }

    /**
     * @return (
     *     $reflection is \ReflectionFunction ? NamedFunctionId|AnonymousFunctionId :
     *     $reflection is \ReflectionClass ? NamedClassId|AnonymousClassId :
     *     $reflection is \ReflectionClassConstant ? ClassConstId :
     *     $reflection is \ReflectionProperty ? PropertyId :
     *     $reflection is \ReflectionMethod ? MethodId :
     *     $reflection is \ReflectionParameter ? ParameterId : never
     * )
     */
    final public static function fromReflection(\ReflectionFunctionAbstract|\ReflectionClass|\ReflectionClassConstant|\ReflectionProperty|\ReflectionParameter $reflection): self
    {
        return match (true) {
            $reflection instanceof \ReflectionFunction => $reflection->name === self::ANONYMOUS_CLOSURE_NAME ? AnonymousFunctionId::doFromReflection($reflection) : NamedFunctionId::doFromReflection($reflection),
            $reflection instanceof \ReflectionClass => $reflection->isAnonymous() ? AnonymousClassId::doFromReflection($reflection) : new NamedClassId($reflection->name),
            $reflection instanceof \ReflectionClassConstant => new ClassConstId(self::fromReflection($reflection->getDeclaringClass()), $reflection->name),
            $reflection instanceof \ReflectionProperty => PropertyId::doFromReflection($reflection),
            $reflection instanceof \ReflectionMethod => new MethodId(self::fromReflection($reflection->getDeclaringClass()), $reflection->name),
            $reflection instanceof \ReflectionParameter => new ParameterId(self::fromReflection($reflection->getDeclaringFunction()), $reflection->name),
        };
    }

    final public static function decode(string $code): self
    {
        /** @var self */
        return self::doDecode(json_decode($code, associative: true, flags: JSON_THROW_ON_ERROR));
    }

    private static function doDecode(mixed $data): mixed
    {
        if (!\is_array($data)) {
            return $data;
        }

        $code = array_shift($data);
        $args = array_map(self::doDecode(...), $data);

        /** @psalm-suppress MixedArgument, UnhandledMatchCondition */
        return match ($code) {
            self::CODE_CONST => new ConstId(...$args),
            self::CODE_NAMED_FUNCTION => new NamedFunctionId(...$args),
            self::CODE_ANONYMOUS_FUNCTION => new AnonymousFunctionId(...$args),
            self::CODE_NAMED_CLASS => new NamedClassId(...$args),
            self::CODE_ANONYMOUS_CLASS => new AnonymousClassId(...$args),
            self::CODE_ALIAS => new AliasId(...$args),
            self::CODE_TEMPLATE => new TemplateId(...$args),
            self::CODE_CLASS_CONST => new ClassConstId(...$args),
            self::CODE_PROPERTY => new PropertyId(...$args),
            self::CODE_METHOD => new MethodId(...$args),
            self::CODE_PARAMETER => new ParameterId(...$args),
        };
    }

    /**
     * @return non-empty-string
     */
    final public function __toString(): string
    {
        return $this->describe();
    }

    /**
     * @return non-empty-string
     */
    abstract public function describe(): string;

    /**
     * @return non-empty-string
     */
    final public function encode(): string
    {
        return json_encode($this, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    abstract public function equals(mixed $value): bool;
}
