<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Test\Fixture\Util\ClassLockPick;


class FixtureSourceParentClass extends FixtureSourceGrandParentClass
{
    private static int $fooStat = 0;
    private $parentPrivate;
    private bool $parentPrivateInitialized = true;

    private static function testPrivStat(): int
    {
        return 10;
    }
}