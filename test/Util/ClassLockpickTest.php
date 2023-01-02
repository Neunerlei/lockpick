<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Test\Util;


use Neunerlei\Lockpick\Test\Fixture\FixtureLockPickTarget;
use Neunerlei\Lockpick\Util\ClassLockpick;
use PHPUnit\Framework\TestCase;

class ClassLockpickTest extends TestCase
{
    public function testPropertyAccess(): void
    {
        $i = new FixtureLockPickTarget();
        $lp = new ClassLockpick($i);

        static::assertTrue($lp->hasProperty('foo'));
        static::assertTrue($lp->hasProperty('fooNotInitialized'));
        static::assertTrue(isset($lp->foo));
        static::assertFalse(isset($lp->fooNotInitialized));

        static::assertFalse($lp->hasProperty('faz'));
        static::assertFalse(isset($lp->faz));

        static::assertEquals(null, $lp->getPropertyValue('asdf'));
        static::assertEquals(null, $lp->asdf);

        static::assertEquals('test', $lp->getPropertyValue('bar'));
        static::assertEquals('test', $lp->bar);

        static::assertEquals(1, $lp->getPropertyValue('foo'));
        static::assertEquals(1, $lp->foo);

        $lp->setPropertyValue('foo', 2);
        static::assertEquals(2, $lp->getPropertyValue('foo'));
        static::assertEquals(2, $lp->foo);

        static::assertNull($lp->getPropertyValue('fooNotInitialized'));
        static::assertNull($lp->fooNotInitialized);

        $lp->foo = 3;
        static::assertEquals(3, $lp->getPropertyValue('foo'));
        static::assertEquals(3, $lp->foo);
    }

    public function testStaticPropertyAccess(): void
    {
        static::assertFalse(ClassLockpick::hasStaticProperty(FixtureLockPickTarget::class, 'foo'));
        static::assertTrue(ClassLockpick::hasStaticProperty(FixtureLockPickTarget::class, 'fooStat'));

        static::assertEquals(0, ClassLockpick::getStaticPropertyValue(FixtureLockPickTarget::class, 'fooStat'));
        static::assertEquals(null, ClassLockpick::getStaticPropertyValue(FixtureLockPickTarget::class, 'asdf'));

        ClassLockpick::setStaticPropertyValue(FixtureLockPickTarget::class, 'fooStat', 2);
        static::assertEquals(2, ClassLockpick::getStaticPropertyValue(FixtureLockPickTarget::class, 'fooStat'));
    }

    public function testMethodExecution(): void
    {
        $i = new FixtureLockPickTarget();
        $lp = new ClassLockpick($i);

        static::assertEquals('test', $lp->runMethod('testProt'));
        static::assertEquals(55, $lp->runMethod('testPriv', [55]));
        static::assertEquals(5, ClassLockpick::runStaticMethod(FixtureLockPickTarget::class, 'testPrivStat'));

        static::assertEquals('test', $lp->testProt());
        static::assertEquals(66, $lp->testPriv(66));

        static::assertTrue($lp->hasMethod('testProt'));
        static::assertFalse($lp->hasMethod('testPrivStat'));
        static::assertTrue(ClassLockpick::hasStaticMethod(FixtureLockPickTarget::class, 'testPrivStat'));
        static::assertFalse(ClassLockpick::hasStaticMethod(FixtureLockPickTarget::class, 'testProt'));
    }
}