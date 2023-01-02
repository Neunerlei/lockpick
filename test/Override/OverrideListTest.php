<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Test\Override;


use Neunerlei\Lockpick\Override\Exception\OverriddenClassAlreadyLoadedException;
use Neunerlei\Lockpick\Override\Exception\OverriddenClassConflictException;
use Neunerlei\Lockpick\Override\OverrideList;
use Neunerlei\Lockpick\Test\Fixture\FixtureExtendedOverrideClass;
use Neunerlei\Lockpick\Test\Fixture\FixtureNotLoadedClass;
use Neunerlei\Lockpick\Test\Fixture\FixtureOverrideClass;
use Neunerlei\Lockpick\Test\Fixture\FixtureSuperExtendedOverrideClass;
use PHPUnit\Framework\TestCase;

class OverrideListTest extends TestCase
{
    public function testRegisterSimpleOverride(): void
    {
        $l = new OverrideList();

        static::assertTrue($l->canOverrideClass(FixtureNotLoadedClass::class));
        static::assertFalse($l->hasClassOverride(FixtureNotLoadedClass::class));

        $l->registerOverride(FixtureNotLoadedClass::class, FixtureOverrideClass::class);

        static::assertFalse($l->canOverrideClass(FixtureNotLoadedClass::class));
        static::assertTrue($l->hasClassOverride(FixtureNotLoadedClass::class));

        static::assertEquals([FixtureNotLoadedClass::class => FixtureOverrideClass::class], $l->getClassStack(FixtureNotLoadedClass::class));
    }

    public function testRegisterOverrideOfOverride(): void
    {
        $l = new OverrideList();
        $l->registerOverride(FixtureNotLoadedClass::class, FixtureOverrideClass::class);
        $l->registerOverride(FixtureOverrideClass::class, FixtureExtendedOverrideClass::class);
        $l->registerOverride(FixtureExtendedOverrideClass::class, FixtureSuperExtendedOverrideClass::class);
        static::assertEquals(
            [
                FixtureNotLoadedClass::class => FixtureOverrideClass::class,
                FixtureOverrideClass::class => FixtureExtendedOverrideClass::class,
                FixtureExtendedOverrideClass::class => FixtureSuperExtendedOverrideClass::class,
            ],
            $l->getClassStack(FixtureNotLoadedClass::class)
        );
    }

    public function testRegisterOverrideFailIfClassIsAlreadyLoaded(): void
    {
        $this->expectException(OverriddenClassAlreadyLoadedException::class);
        $this->expectExceptionMessage(
            'The class: ' . OverrideListTest::class . ' can not be overridden, because it is already loaded!');

        (new OverrideList())->registerOverride(OverrideListTest::class, FixtureNotLoadedClass::class);
    }

    public function testRegisterOverrideFailIfAnOverrideAlreadyExists(): void
    {
        $this->expectException(OverriddenClassConflictException::class);
        $this->expectExceptionMessage(
            'The class: ' . FixtureNotLoadedClass::class . ' is already overridden with: ' . FixtureOverrideClass::class .
            ' and therefore, can not be overridden again!');

        $l = new OverrideList();
        $l->registerOverride(FixtureNotLoadedClass::class, FixtureOverrideClass::class);
        $l->registerOverride(FixtureNotLoadedClass::class, FixtureOverrideClass::class);
    }

    public function testRegisterOverrideWithOverruleOptionSet(): void
    {
        $l = new OverrideList();
        $l->registerOverride(FixtureNotLoadedClass::class, FixtureOverrideClass::class);
        $l->registerOverride(FixtureNotLoadedClass::class, FixtureExtendedOverrideClass::class, true);
        static::assertEquals(
            [FixtureNotLoadedClass::class => FixtureExtendedOverrideClass::class],
            $l->getClassStack(FixtureNotLoadedClass::class)
        );
    }

    public function testCanOverrideClass(): void
    {
        $l = new OverrideList();

        static::assertTrue($l->canOverrideClass(FixtureNotLoadedClass::class));
        static::assertFalse($l->canOverrideClass(OverrideListTest::class));

        $l->registerOverride(FixtureNotLoadedClass::class, FixtureOverrideClass::class);

        static::assertFalse($l->canOverrideClass(FixtureNotLoadedClass::class));
        static::assertTrue($l->canOverrideClass(FixtureNotLoadedClass::class, true));
    }
}