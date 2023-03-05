<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Test\Fixture\Util\ClassLockPick;


class FixtureSourceClass extends FixtureSourceParentClass
{
    private bool $sourcePrivate;
    private bool $sourcePrivateInitialized = true;

    private function testPriv(int $val): int
    {
        return $val;
    }
}