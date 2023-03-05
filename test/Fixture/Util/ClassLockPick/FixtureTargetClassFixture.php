<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Test\Fixture\Util\ClassLockPick;


class FixtureTargetClassFixture extends FixtureSourceClass
{
    private int $foo = 1;
    private int $fooNotInitialized;
    protected string $bar = 'test';

    protected function testProt(): string
    {
        return 'test';
    }

    public function hello(): string
    {
        return $this->testProt();
    }
}