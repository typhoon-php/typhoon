<?php

declare(strict_types=1);

namespace Typhoon\DataStructure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(KVPair::class)]
final class KVPairTest extends TestCase
{
    public function testWithKey(): void
    {
        $kv = new KVPair('k', 'v');

        $newKv = $kv->withKey('k2');

        self::assertNotSame($kv, $newKv);
        self::assertSame('k2', $newKv->key);
        self::assertSame('v', $newKv->value);
    }

    public function testWithValue(): void
    {
        $kv = new KVPair('k', 'v');

        $newKv = $kv->withValue('v2');

        self::assertNotSame($kv, $newKv);
        self::assertSame('k', $newKv->key);
        self::assertSame('v2', $newKv->value);
    }

    public function testFlip(): void
    {
        $kv = new KVPair('k', 'v');

        $newKv = $kv->flip();

        self::assertNotSame($kv, $newKv);
        self::assertSame('v', $newKv->key);
        self::assertSame('k', $newKv->value);
    }
}
