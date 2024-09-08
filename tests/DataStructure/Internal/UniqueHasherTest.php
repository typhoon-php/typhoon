<?php

declare(strict_types=1);

namespace Typhoon\DataStructure\Internal;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use function Typhoon\DataStructure\registerObjectHasher;

#[CoversClass(UniqueHasher::class)]
final class UniqueHasherTest extends TestCase
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
        $hash = UniqueHasher::hash($value);

        self::assertSame($expected, $hash);
    }

    /**
     * @param resource $resource
     */
    #[TestWith([STDIN])]
    #[TestWith([STDOUT])]
    #[TestWith([STDERR])]
    public function testResource(mixed $resource): void
    {
        $hash = UniqueHasher::hash($resource);

        self::assertSame('r' . get_resource_id($resource), $hash);
    }

    public function testObject(): void
    {
        $hash = UniqueHasher::hash($this);

        self::assertSame('#' . spl_object_id($this), $hash);
    }

    #[RunInSeparateProcess]
    public function testObjectWithCustomEncoder(): void
    {
        registerObjectHasher(\Throwable::class, static fn(\Throwable $exception): string => $exception->getMessage());
        registerObjectHasher(\RuntimeException::class, static fn(\RuntimeException $exception): string => $exception->getMessage());
        registerObjectHasher(\RangeException::class, static fn(\RangeException $exception): string => $exception->getMessage());

        self::assertSame('Throwable@`logic`', UniqueHasher::hash(new \LogicException('logic')));
        self::assertSame('RuntimeException@`runtime`', UniqueHasher::hash(new \RuntimeException('runtime')));
        self::assertSame('RuntimeException@`overflow`', UniqueHasher::hash(new \OverflowException('overflow')));
        self::assertSame('RangeException@`range`', UniqueHasher::hash(new \RangeException('range')));
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
        registerObjectHasher(self::class, static fn(): bool => true, $prefix);

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

        registerObjectHasher(self::class, static fn(): bool => true, $prefix);
    }

    #[RunInSeparateProcess]
    public function testRegisterObjectEncoderThrowsAfterEncoding(): void
    {
        UniqueHasher::hash(1);

        $this->expectExceptionObject(new \LogicException('Please register object encoders before using data structures'));

        registerObjectHasher(self::class, static fn(): bool => true);
    }
}
