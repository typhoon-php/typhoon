<?php

declare(strict_types=1);

namespace Typhoon\DataStructure\Internal;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use function Typhoon\DataStructure\registerObjectEncoder;

#[CoversClass(Encoder::class)]
final class EncoderTest extends TestCase
{
    #[TestWith([null, 'n'])]
    #[TestWith([true, 't'])]
    #[TestWith([false, 'f'])]
    #[TestWith([0, 0])]
    #[TestWith([1, 1])]
    #[TestWith([-100, -100])]
    #[TestWith([0.5, '0.5'])]
    #[TestWith([-0.5, '-0.5'])]
    #[TestWith([NAN, 'NAN'])]
    #[TestWith([INF, 'INF'])]
    #[TestWith(['', '``'])]
    #[TestWith(['test', '`test`'])]
    #[TestWith(['`', '`\``'])]
    #[TestWith(['\`', '`\\\``'])]
    #[TestWith(['```', '`\`\`\``'])]
    #[TestWith([[], '[]'])]
    #[TestWith([[1, 2, 3], '[1,2,3,]'])]
    #[TestWith([['a' => 'b'], '[`a`:`b`,]'])]
    public function testSimpleValues(mixed $value, int|string $expected): void
    {
        $encoded = Encoder::encode($value);

        self::assertSame($expected, $encoded);
    }

    /**
     * @param resource $resource
     */
    #[TestWith([STDIN])]
    #[TestWith([STDOUT])]
    #[TestWith([STDERR])]
    public function testResource(mixed $resource): void
    {
        $encoded = Encoder::encode($resource);

        self::assertSame('r' . get_resource_id($resource), $encoded);
    }

    public function testObject(): void
    {
        $encoded = Encoder::encode($this);

        self::assertSame('#' . spl_object_id($this), $encoded);
    }

    #[RunInSeparateProcess]
    public function testObjectWithCustomEncoder(): void
    {
        registerObjectEncoder(\Throwable::class, static fn(\Throwable $exception): string => $exception->getMessage());
        registerObjectEncoder(\RuntimeException::class, static fn(\RuntimeException $exception): string => $exception->getMessage());
        registerObjectEncoder(\RangeException::class, static fn(\RangeException $exception): string => $exception->getMessage());

        self::assertSame('Throwable@`logic`', Encoder::encode(new \LogicException('logic')));
        self::assertSame('RuntimeException@`runtime`', Encoder::encode(new \RuntimeException('runtime')));
        self::assertSame('RuntimeException@`overflow`', Encoder::encode(new \OverflowException('overflow')));
        self::assertSame('RangeException@`range`', Encoder::encode(new \RangeException('range')));
    }

    /**
     * @param non-empty-string $prefix
     */
    #[TestWith(['abc'])]
    #[TestWith([self::class])]
    #[TestWith(['123'])]
    #[TestWith(['a.b.c'])]
    #[RunInSeparateProcess]
    public function testRegisterObjectEncoderAcceptsValidPrefix(string $prefix): void
    {
        registerObjectEncoder(self::class, static fn(): bool => true, $prefix);

        self::expectNotToPerformAssertions();
    }

    /**
     * @param non-empty-string $prefix
     */
    #[TestWith(['['])]
    #[TestWith([']'])]
    #[TestWith(['#'])]
    #[TestWith(['@'])]
    #[TestWith([','])]
    #[RunInSeparateProcess]
    public function testRegisterObjectEncoderThrowsOnInvalidPrefix(string $prefix): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException(\sprintf('Invalid prefix "%s"', $prefix)));

        registerObjectEncoder(self::class, static fn(): bool => true, $prefix);
    }

    #[RunInSeparateProcess]
    public function testRegisterObjectEncoderThrowsAfterEncoding(): void
    {
        Encoder::encode(1);

        $this->expectExceptionObject(new \LogicException('Please register object encoders before using data structures'));

        registerObjectEncoder(self::class, static fn(): bool => true);
    }
}
