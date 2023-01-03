<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Override;


class AutoLoader
{
    protected OverrideList $overrideList;
    protected OverrideStackResolver $stackResolver;

    protected bool $isRegistered = false;

    public function __construct(
        OverrideList          $overrideList,
        OverrideStackResolver $stackResolver
    )
    {
        $this->overrideList = $overrideList;
        $this->stackResolver = $stackResolver;

        $overrideList->setAutoLoader($this);
    }

    /**
     * Registers the autoloader in the system
     */
    public function register(): void
    {
        if ($this->isRegistered) {
            return;
        }

        spl_autoload_register([$this, 'loadClass'], true, true);
        $this->isRegistered = true;
    }

    /**
     * Removes the autoloader from the system
     */
    public function unregister(): void
    {
        if (!$this->isRegistered) {
            return;
        }

        spl_autoload_unregister([$this, 'loadClass']);
        $this->isRegistered = false;
    }

    /**
     * Returns the instance of the override list which is used to resolve our overrides
     *
     * @return OverrideList
     */
    public function getOverrideList(): OverrideList
    {
        return $this->overrideList;
    }

    /**
     * Returns the instance of the override stack resolver, which is used to provide the actual override implementations
     * @return OverrideStackResolver
     */
    public function getStackResolver(): OverrideStackResolver
    {
        return $this->stackResolver;
    }

    /**
     * Our own spl autoload function
     *
     * @param string $class The name of the class that be loaded
     *
     * @return bool
     */
    public function loadClass(string $class): bool
    {
        if (class_exists($class, false) || interface_exists($class, false)) {
            return false;
        }

        $stack = $this->overrideList->getClassStack($class);
        if (is_array($stack)) {
            $this->stackResolver->resolve($stack);
            return true;
        }

        return false;
    }

    /**
     * Builds the alias/clone codes and dumps them to the file system if not already present
     * @param string $class
     * @return bool
     */
    public function buildClass(string $class): bool
    {
        $stack = $this->overrideList->getClassStack($class);
        if (is_array($stack)) {
            $this->stackResolver->resolve($stack, false);
            return true;
        }

        return false;
    }
}