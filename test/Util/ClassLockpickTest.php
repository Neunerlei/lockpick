<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Test\Util;


use Neunerlei\Lockpick\Test\Fixture\Util\ClassLockPick\FixtureTargetClassFixture;
use Neunerlei\Lockpick\Util\ClassLockpick;
use PHPUnit\Framework\TestCase;

class ClassLockpickTest extends TestCase
{
    public function testPropertyAccess(): void
    {
        $i = new FixtureTargetClassFixture();
        $lp = new ClassLockpick($i);

        static::assertEquals([
            'foo',
            'fooNotInitialized',
            'bar',
            'sourcePrivate',
            'sourcePrivateInitialized',
            'parentPrivate',
            'parentPrivateInitialized',
            'grandParentPrivate',
            'grandParentPrivateInitialized'
        ], $lp->getPropertyNames());

        // Fixture
        static::assertTrue($lp->hasProperty('foo'));
        static::assertTrue($lp->hasProperty('fooNotInitialized'));
        static::assertTrue(isset($lp->foo));
        static::assertTrue(isset($lp->bar));
        static::assertFalse(isset($lp->fooNotInitialized));

        static::assertEquals(1, $lp->foo);
        static::assertEquals(1, $lp->getPropertyValue('foo'));
        static::assertNull($lp->getPropertyValue('fooNotInitialized'));
        static::assertEquals('test', $lp->getPropertyValue('bar'));

        $lp->foo = 5;
        static::assertEquals(5, $lp->foo);
        static::assertEquals(5, $lp->getPropertyValue('foo'));

        $lp->setPropertyValue('foo', 5);
        static::assertEquals(5, $lp->foo);
        static::assertEquals(5, $lp->getPropertyValue('foo'));

        // Source class
        static::assertTrue($lp->hasProperty('sourcePrivate'));
        static::assertTrue($lp->hasProperty('sourcePrivateInitialized'));
        static::assertTrue(isset($lp->sourcePrivateInitialized));
        static::assertFalse(isset($lp->sourcePrivate));

        // Source Parent class
        static::assertTrue($lp->hasProperty('parentPrivate'));
        static::assertTrue($lp->hasProperty('parentPrivateInitialized'));
        static::assertFalse($lp->hasProperty('fooStat'));
        static::assertFalse(isset($lp->fooStat));
    }

    public function testStaticPropertyAccess(): void
    {
        static::assertFalse(ClassLockpick::hasStaticProperty(FixtureTargetClassFixture::class, 'foo'));
        static::assertTrue(ClassLockpick::hasStaticProperty(FixtureTargetClassFixture::class, 'fooStat'));

        static::assertEquals(0, ClassLockpick::getStaticPropertyValue(FixtureTargetClassFixture::class, 'fooStat'));
        static::assertEquals(null, ClassLockpick::getStaticPropertyValue(FixtureTargetClassFixture::class, 'notDefined'));

        ClassLockpick::setStaticPropertyValue(FixtureTargetClassFixture::class, 'fooStat', 2);
        static::assertEquals(2, ClassLockpick::getStaticPropertyValue(FixtureTargetClassFixture::class, 'fooStat'));
    }

    public function testMethodExecution(): void
    {
        $i = new FixtureTargetClassFixture();
        $lp = new ClassLockpick($i);

        static::assertEquals('test', $lp->runMethod('testProt'));
        static::assertEquals(55, $lp->runMethod('testPriv', [55]));
        static::assertEquals(10, ClassLockpick::runStaticMethod(FixtureTargetClassFixture::class, 'testPrivStat'));
        static::assertEquals('test', $lp->testProt());
        static::assertEquals(66, $lp->testPriv(66));

        static::assertTrue($lp->hasMethod('testProt'));
        static::assertFalse($lp->hasMethod('testPrivStat'));
        static::assertTrue(ClassLockpick::hasStaticMethod(FixtureTargetClassFixture::class, 'testPrivStat'));
        static::assertFalse(ClassLockpick::hasStaticMethod(FixtureTargetClassFixture::class, 'testProt'));
    }
}