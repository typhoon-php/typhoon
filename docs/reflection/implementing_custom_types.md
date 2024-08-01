# Implementing custom types

```php
use Typhoon\Reflection\Annotated\CustomTypeResolver;
use Typhoon\Reflection\Annotated\TypeContext;
use Typhoon\Reflection\TyphoonReflector;
use Typhoon\Type\Type;
use Typhoon\Type\types;
use Typhoon\Type\TypeVisitor;
use function Typhoon\Type\stringify;

/**
 * @implements Type<int|float>
 */
enum binary: string implements Type, CustomTypeResolver
{
    case int16 = 'int16';
    case int32 = 'int32';
    case int64 = 'int64';
    case float32 = 'float32';
    case float64 = 'float64';

    public function accept(TypeVisitor $visitor): mixed
    {
        /**
         * We need to suppress here, because Psalm does not support var annotations on enum cases yet ;(
         * @psalm-suppress InvalidArgument
         */
        return match ($this) {
            self::int16 => $visitor->int($this, types::int(-32768), types::int(32767)),
            self::int32 => $visitor->int($this, types::int(-2147483648), types::int(2147483647)),
            self::int64 => $visitor->int($this, types::PHP_INT_MIN, types::PHP_INT_MAX),
            self::float32 => $visitor->float($this, types::float(-3.40282347E+38), types::float(3.40282347E+38)),
            self::float64 => $visitor->float($this, types::PHP_FLOAT_MIN, types::PHP_FLOAT_MAX),
        };
    }

    public function resolveCustomType(string $name, array $typeArguments, TypeContext $context): ?Type
    {
        return self::tryFrom($name);
    }
}

final readonly class Message
{
    /**
     * @param list<int16> $some16bitIntegers
     */
    public function __construct(
        public array $some16bitIntegers,
    ) {}
}

$reflector = TyphoonReflector::build(customTypeResolver: binary::int16);

$propertyType = $reflector
    ->reflectClass(Message::class)
    ->properties()['some16bitIntegers']
    ->type();

var_dump(stringify($propertyType)); // "list<int<-32768, 32767>>"
```
