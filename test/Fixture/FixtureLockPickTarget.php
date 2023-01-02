<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Test\Fixture;


final class FixtureLockPickTarget
{
    private static int $fooStat = 0;

    private int $foo = 1;
    private int $fooNotInitialized;
    protected string $bar = 'test';

    protected function testProt(): string
    {
        return 'test';
    }

    private function testPriv(int $val): int
    {
        return $val;
    }

    private static function testPrivStat(): int
    {
        return 5;
    }
}