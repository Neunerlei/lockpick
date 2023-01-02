<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Test\Override;


use Neunerlei\Lockpick\Override\AutoLoader;
use Neunerlei\Lockpick\Override\ClassOverrider;
use Neunerlei\Lockpick\Override\CodeGenerator;
use Neunerlei\Lockpick\Override\Driver\DefaultIoDriver;
use Neunerlei\Lockpick\Override\OverrideList;
use Neunerlei\Lockpick\Override\OverrideStackResolver;
use Neunerlei\Lockpick\Test\Fixture\FixtureExtendedOverrideClass;
use Neunerlei\Lockpick\Test\Fixture\FixtureOverrideClass;
use Neunerlei\Lockpick\Test\Fixture\FixturePrivateObject;
use Neunerlei\Lockpick\Test\Fixture\FixtureSimpleClass;
use Neunerlei\Lockpick\Test\Fixture\FixtureSuperExtendedOverrideClass;
use Neunerlei\Lockpick\Util\ClassLockpick;
use PHPUnit\Framework\TestCase;

class ClassOverriderTest extends TestCase
{
    protected function setUp(): void
    {
        ClassOverrider::init(require __DIR__ . '/../../vendor/autoload.php');
        ClassOverrider::flushStorage();

        parent::setUp();
    }

    protected function tearDown(): void
    {
        ClassOverrider::flushStorage();
        ClassOverrider::getAutoLoader()->unregister();

        parent::tearDown();
    }

    public function testInitialization(): void
    {
        $loader = ClassOverrider::getAutoLoader();

        $list = new OverrideList();
        $resolver = new OverrideStackResolver(
            new DefaultIoDriver(),
            fn() => new CodeGenerator(require __DIR__ . '/../../vendor/autoload.php')
        );
        $newLoader = new AutoLoader(
            $list,
            $resolver
        );

        ClassOverrider::init($newLoader);

        // The old loader is unregistered when the new one gets registered
        static::assertFalse((new ClassLockpick($loader))->getPropertyValue('isRegistered'));
        static::assertTrue((new ClassLockpick($newLoader))->getPropertyValue('isRegistered'));
    }

    public function testOverrideListMethodsPassThrough(): void
    {
        ClassOverrider::init(require __DIR__ . '/../../vendor/autoload.php', true);
        $list = ClassOverrider::getAutoLoader()->getOverrideList();

        static::assertFalse(ClassOverrider::hasClassOverride(FixtureSimpleClass::class));
        static::assertTrue(ClassOverrider::canOverrideClass(FixtureSimpleClass::class));
        static::assertFalse($list->hasClassOverride(FixtureSimpleClass::class));
        static::assertTrue($list->canOverrideClass(FixtureSimpleClass::class));

        ClassOverrider::registerOverride(FixtureSimpleClass::class, FixtureExtendedOverrideClass::class);

        static::assertTrue(ClassOverrider::hasClassOverride(FixtureSimpleClass::class));
        static::assertFalse(ClassOverrider::canOverrideClass(FixtureSimpleClass::class));
        static::assertTrue($list->hasClassOverride(FixtureSimpleClass::class));
        static::assertFalse($list->canOverrideClass(FixtureSimpleClass::class));
    }

    public function testGenerationOfNonPreloadableClasses(): void
    {
        ClassOverrider::init(require __DIR__ . '/../../vendor/autoload.php', true);

        ClassOverrider::registerOverride(FixturePrivateObject::class, FixtureOverrideClass::class);
        ClassOverrider::registerOverride(FixtureOverrideClass::class, FixtureExtendedOverrideClass::class);
        ClassOverrider::registerOverride(FixtureExtendedOverrideClass::class, FixtureSuperExtendedOverrideClass::class);

        static::assertEquals([
            FixturePrivateObject::class,
            FixtureOverrideClass::class,
            FixtureExtendedOverrideClass::class,
            FixtureSuperExtendedOverrideClass::class,
        ], ClassOverrider::getNotPreloadableClasses());
    }
}