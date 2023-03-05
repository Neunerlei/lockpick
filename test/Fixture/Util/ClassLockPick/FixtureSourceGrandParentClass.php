<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Test\Fixture\Util\ClassLockPick;


class FixtureSourceGrandParentClass
{
    private $grandParentPrivate;
    private bool $grandParentPrivateInitialized = true;

    private static function testPrivStat(): int
    {
        return 5;
    }
}