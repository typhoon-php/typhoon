<?php

declare(strict_types=1);

namespace Typhoon\Reflection\Exporter;

/**
 * @internal
 * @psalm-internal Typhoon\Reflection
 */
final class Hydrator
{
    /**
     * @var array<class-string, object>
     */
    private array $prototypes = [];

    /**
     * @var array<class-string, \Closure(object, array<string, mixed>): void>
     */
    private array $setters = [];

    /**
     * @psalm-suppress PossiblyUnusedMethod
     * @param non-empty-array<class-string, array<string, mixed>> $data
     */
    public function hydrate(array $data): object
    {
        $object = clone $this->prototype(array_key_first($data));

        if (method_exists($object, '__unserialize')) {
            $object->__unserialize(array_merge(...array_values($data)));

            return $object;
        }

        foreach ($data as $class => $values) {
            $this->setter($class)($object, $values);
        }

        return $object;
    }

    /**
     * @param class-string $class
     */
    private function prototype(string $class): object
    {
        return $this->prototypes[$class] ??= (new \ReflectionClass($class))->newInstanceWithoutConstructor();
    }

    /**
     * @param class-string $class
     * @return \Closure(object, array<string, mixed>): void
     */
    private function setter(string $class): \Closure
    {
        if (isset($this->setters[$class])) {
            return $this->setters[$class];
        }

        /** @var \Closure(object, array<string, mixed>): void */
        $setter = (static function (object $object, array $values): void {
            foreach ($values as $property => $value) {
                $object->{$property} = $value;
            }
        })->bindTo(null, $class);

        return $this->setters[$class] = $setter;
    }
}
