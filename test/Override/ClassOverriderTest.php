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
use Neunerlei\Lockpick\Test\Fixture\FixtureNotLoadedClass;
use Neunerlei\Lockpick\Util\ClassLockpick;
use PHPUnit\Framework\TestCase;

class ClassOverriderTest extends TestCase
{
    protected $loaderBackup;

    protected function setUp(): void
    {
        ClassOverrider::init(require __DIR__ . '/../../vendor/autoload.php');
        $this->loaderBackup = ClassOverrider::getAutoLoader();
        parent::setUp();
    }

    protected function tearDown(): void
    {
        if ($this->loaderBackup instanceof AutoLoader) {
            ClassOverrider::init($this->loaderBackup, true);
        }

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

        ClassOverrider::init($newLoader, false);

        // The old loader is unregistered when the new one gets registered
        static::assertFalse((new ClassLockpick($loader))->getPropertyValue('isRegistered'));
        static::assertTrue((new ClassLockpick($newLoader))->getPropertyValue('isRegistered'));

        // Test mode should now be false
        static::assertFalse((new ClassLockpick($list))->getPropertyValue('isTestMode'));

        // Test mode should now be true
        ClassOverrider::init($newLoader, true);
        static::assertTrue((new ClassLockpick($newLoader))->getPropertyValue('isRegistered'));
        static::assertTrue((new ClassLockpick($list))->getPropertyValue('isTestMode'));
    }

    public function testOverrideListMethodsPassThrough(): void
    {
        ClassOverrider::init(require __DIR__ . '/../../vendor/autoload.php', true);
        $list = ClassOverrider::getAutoLoader()->getOverrideList();

        static::assertFalse(ClassOverrider::hasClassOverride(FixtureNotLoadedClass::class));
        static::assertTrue(ClassOverrider::canOverrideClass(FixtureNotLoadedClass::class));
        static::assertFalse($list->hasClassOverride(FixtureNotLoadedClass::class));
        static::assertTrue($list->canOverrideClass(FixtureNotLoadedClass::class));

        ClassOverrider::registerOverride(FixtureNotLoadedClass::class, FixtureExtendedOverrideClass::class);

        static::assertTrue(ClassOverrider::hasClassOverride(FixtureNotLoadedClass::class));
        static::assertFalse(ClassOverrider::canOverrideClass(FixtureNotLoadedClass::class));
        static::assertTrue($list->hasClassOverride(FixtureNotLoadedClass::class));
        static::assertFalse($list->canOverrideClass(FixtureNotLoadedClass::class));
    }
}