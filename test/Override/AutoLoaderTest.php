<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Test\Override;


use Neunerlei\Lockpick\Override\AutoLoader;
use Neunerlei\Lockpick\Override\OverrideList;
use Neunerlei\Lockpick\Override\OverrideStackResolver;
use Neunerlei\Lockpick\Test\Fixture\FixtureAutoloadClass;
use Neunerlei\Lockpick\Test\Fixture\FixtureAutoloadInterface;
use Neunerlei\Lockpick\Util\ClassLockpick;
use PHPUnit\Framework\TestCase;

class AutoLoaderTest extends TestCase
{
    public function testRegistration(): void
    {
        $loader = new AutoLoader(
            $this->createMock(OverrideList::class),
            $this->createMock(OverrideStackResolver::class)
        );

        $checker = static function () use ($loader) {
            foreach (spl_autoload_functions() as $l) {
                if (is_array($l) && ($l[0] ?? null) === $loader && ($l[1] ?? null) === 'loadClass') {
                    return true;
                }
            }

            return false;
        };

        $lockPick = new ClassLockpick($loader);

        static::assertFalse($lockPick->getPropertyValue('isRegistered'));
        static::assertFalse($checker());

        $loader->register();

        static::assertTrue($lockPick->getPropertyValue('isRegistered'));
        static::assertTrue($checker());

        $loader->register();

        static::assertTrue($lockPick->getPropertyValue('isRegistered'));
        static::assertTrue($checker());

        $loader->unregister();

        static::assertFalse($lockPick->getPropertyValue('isRegistered'));
        static::assertFalse($checker());

        $loader->unregister();

        static::assertFalse($lockPick->getPropertyValue('isRegistered'));
        static::assertFalse($checker());
    }

    public function testOverrideListRetrieval(): void
    {
        $list = $this->createMock(OverrideList::class);
        $loader = new AutoLoader(
            $list,
            $this->createMock(OverrideStackResolver::class)
        );

        static::assertSame($list, $loader->getOverrideList());
    }

    public function testLoadClass(): void
    {
        $list = $this->createMock(OverrideList::class);
        $list->method('getClassStack')->willReturn([]);

        $resolver = $this->createMock(OverrideStackResolver::class);
        $resolver->method('resolve')->willReturn([]);

        $loader = new AutoLoader(
            $list,
            $resolver
        );

        static::assertTrue($loader->loadClass(FixtureAutoloadClass::class));
        static::assertTrue($loader->loadClass(FixtureAutoloadInterface::class));

        // Already loaded elements
        static::assertFalse($loader->loadClass(AutoLoader::class));
        static::assertFalse($loader->loadClass(OverrideStackResolver::class));

        // Simulate not registered override handling
        $list = $this->createMock(OverrideList::class);
        $list->method('getClassStack')->willReturn(null);

        $loader = new AutoLoader(
            $list,
            $resolver
        );

        static::assertFalse($loader->loadClass(FixtureAutoloadClass::class));
        static::assertFalse($loader->loadClass(FixtureAutoloadInterface::class));
    }
}