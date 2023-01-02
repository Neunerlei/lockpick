<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Test\Util;


use Neunerlei\Lockpick\Test\Fixture\FixturePrivateObject;
use Neunerlei\Lockpick\Util\ClassLockpick;
use PHPUnit\Framework\TestCase;

class ClassLockpickTest extends TestCase
{
    public function testPropertyAccess(): void
    {
        $i = new FixturePrivateObject();
        $lp = new ClassLockpick($i);

        static::assertTrue($lp->hasProperty('foo'));
        static::assertTrue(isset($lp->foo));

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

        $lp->foo = 3;
        static::assertEquals(3, $lp->getPropertyValue('foo'));
        static::assertEquals(3, $lp->foo);
    }

    public function testStaticPropertyAccess(): void
    {
        static::assertFalse(ClassLockpick::hasStaticProperty(FixturePrivateObject::class, 'foo'));
        static::assertTrue(ClassLockpick::hasStaticProperty(FixturePrivateObject::class, 'fooStat'));

        static::assertEquals(0, ClassLockpick::getStaticPropertyValue(FixturePrivateObject::class, 'fooStat'));
        static::assertEquals(null, ClassLockpick::getStaticPropertyValue(FixturePrivateObject::class, 'asdf'));

        ClassLockpick::setStaticPropertyValue(FixturePrivateObject::class, 'fooStat', 2);
        static::assertEquals(2, ClassLockpick::getStaticPropertyValue(FixturePrivateObject::class, 'fooStat'));
    }

    public function testMethodExecution(): void
    {
        $i = new FixturePrivateObject();
        $lp = new ClassLockpick($i);

        static::assertEquals('test', $lp->runMethod('testProt'));
        static::assertEquals(55, $lp->runMethod('testPriv', [55]));
        static::assertEquals(5, ClassLockpick::runStaticMethod(FixturePrivateObject::class, 'testPrivStat'));

        static::assertEquals('test', $lp->testProt());
        static::assertEquals(66, $lp->testPriv(66));

        static::assertTrue($lp->hasMethod('testProt'));
        static::assertFalse($lp->hasMethod('testPrivStat'));
        static::assertTrue(ClassLockpick::hasStaticMethod(FixturePrivateObject::class, 'testPrivStat'));
        static::assertFalse(ClassLockpick::hasStaticMethod(FixturePrivateObject::class, 'testProt'));
    }
}