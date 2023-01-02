<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Override;


use Composer\Autoload\ClassLoader;
use Neunerlei\Lockpick\Override\Driver\DefaultIoDriver;
use Psr\EventDispatcher\EventDispatcherInterface;

class ClassOverrider
{
    public const CLASS_COPY_PREFIX = 'LockpickClassOverride';

    protected static AutoLoader $autoLoader;

    protected function __construct()
    {
    }

    /**
     * Factory to create an autoloader instance based on a storage path (were to put the compiled files)
     * @param string $storagePath
     * @param ClassLoader $classLoader
     * @param EventDispatcherInterface|null $eventDispatcher
     * @return AutoLoader
     */
    public static function makeAutoLoaderByStoragePath(
        string                    $storagePath,
        ClassLoader               $classLoader,
        ?EventDispatcherInterface $eventDispatcher = null): AutoLoader
    {
        return new AutoLoader(
            new OverrideList(),
            new OverrideStackResolver(
                new DefaultIoDriver($storagePath),
                fn() => $classLoader,
                $eventDispatcher
            )
        );
    }

    /**
     * Called once in the boot phase of your application to register our additional autoloader
     *
     * @param ClassLoader|AutoLoader $autoLoader Either the composer class loader instance,
     *                                           or an already prepared instance of the internal autoloader.
     *                                           You can use {@see makeAutoLoaderByStoragePath} as factory.
     * @param bool|mixed $testMode
     */
    public static function init(ClassLoader|AutoLoader $autoLoader, ?bool $testMode = null): void
    {
        if (isset(static::$autoLoader)) {
            static::$autoLoader->unregister();
        }

        if ($autoLoader instanceof ClassLoader) {
            $autoLoader = static::makeAutoLoaderByStoragePath(sys_get_temp_dir(), $autoLoader);
        }

        $autoLoader->setTestMode(
            $testMode ?? str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'phpunit')
        );

        $autoLoader->register();

        static::$autoLoader = $autoLoader;
    }

    /**
     * Returns the internal autoloader instance we use, to inject our clones
     *
     * @return AutoLoader
     */
    public static function getAutoLoader(): AutoLoader
    {
        if (!isset(static::$autoLoader)) {
            throw new \RuntimeException('Sorry, but you have to initialize the class overrider using the "init" method first!');
        }

        return static::$autoLoader;
    }

    /**
     * Allows you to inject the event dispatcher instance even after the autoloader has already been registered
     * @param EventDispatcherInterface $eventDispatcher
     * @return void
     */
    public static function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        if (!isset(static::$autoLoader)) {
            throw new \RuntimeException('Sorry, but you have to initialize the class overrider using the "init" method first!');
        }

        static::getAutoLoader()->getStackResolver()->setEventDispatcher($eventDispatcher);
    }

    /**
     * Registers a new class override. The override will completely replace the original source class.
     * The overwritten class will be copied and is available in the same namespace but with the
     * "LockpickClassOverride" prefix in front of it's class name. The overwritten class has all its private
     * properties and function changed to protected for easier overrides.
     *
     * This method throws an exception if the class is already overwritten by another class
     *
     * @param string $classToOverride The name of the class to overwrite with the class given in
     *                                $classToOverrideWith
     * @param string $classToOverrideWith The name of the class that should be used instead of the class defined as
     *                                    $classToOverride
     * @param bool $overrule If this is set to true already registered overrides can be changed to a
     *                       different definition
     */
    public static function registerOverride(
        string $classToOverride,
        string $classToOverrideWith,
        bool   $overrule = false
    ): void
    {
        static::$autoLoader->getOverrideList()->registerOverride(...func_get_args());
    }

    /**
     * Returns true if the given class can be overwritten with something else
     *
     * @param string $classToOverride The name of the class to check for
     * @param bool $withOverrule Set this to true if you want to allow overruling of the existing definition
     *
     * @return bool
     */
    public static function canOverrideClass(string $classToOverride, bool $withOverrule = false): bool
    {
        return static::$autoLoader->getOverrideList()->canOverrideClass(...func_get_args());
    }

    /**
     * Returns true if the class with the given name is registered as override
     *
     * @param string $classToOverride The name of the class to check for
     *
     * @return bool
     */
    public static function hasClassOverride(string $classToOverride): bool
    {
        return static::$autoLoader->getOverrideList()->hasClassOverride(...func_get_args());
    }

    /**
     * Removes all locally cached files of class overrides that were generated.
     *
     * NOTE: This will not affect the current request, as the classes are already loaded into the memory!
     *
     * @return void
     */
    public static function flushStorage(): void
    {
        static::$autoLoader->getStackResolver()->getIoDriver()->flush();
    }
}