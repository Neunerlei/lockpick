<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Override;


use Neunerlei\Lockpick\Override\Exception\OverriddenClassAlreadyLoadedException;
use Neunerlei\Lockpick\Override\Exception\OverriddenClassConflictException;
use Throwable;

class OverrideList
{
    /**
     * The list of class overrides that are registered
     *
     * @var array
     */
    protected array $overrideDefinitions = [];

    /**
     * True if the script is executed in phpunit
     *
     * @var bool
     */
    protected bool $isTestMode = false;

    /**
     * Special noodle flag to allow override registration, even if the
     * class to be overwritten was already loaded.
     *
     * While in 99% of the cases the check for failing on already loaded classes
     * makes sense and prevents issues, there is a fraction of edge cases where this can cause issues.
     * For example when a cli command clears the cache and the container is being rebuilt at the same time.
     *
     * WARNING: Use this with caution and only if you really need it!
     *
     * @var bool
     */
    protected bool $allowToRegisterLoadedClasses = false;

    protected AutoLoader $autoLoader;

    /**
     * Used to toggle the internal test mode flag
     *
     * @param bool $isTestMode
     *
     * @return self
     * @internal
     */
    public function setTestMode(bool $isTestMode): self
    {
        $this->isTestMode = $isTestMode;
        return $this;
    }

    /**
     * Used to inject the auto-loader for testing purposes
     *
     * @param AutoLoader $autoLoader
     *
     * @return self
     * @internal
     */
    public function setAutoLoader(AutoLoader $autoLoader): self
    {
        $this->autoLoader = $autoLoader;
        return $this;
    }

    /**
     * Defines if it is allowed to register already loaded classes for an override or not
     * @param bool $state
     * @return $this
     * @see self::$allowToRegisterLoadedClasses for further details
     */
    public function setAllowToRegisterLoadedClasses(bool $state): self
    {
        $this->allowToRegisterLoadedClasses = $state;
        return $this;
    }

    /**
     * Registers a new class override. The override will completely replace the original source class.
     * The overwritten class will be copied and is available in the same namespace but with the
     * "T3baCopy" prefix in front of it's class name. The overwritten class has all it's private
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
    public function registerOverride(
        string $classToOverride,
        string $classToOverrideWith,
        bool   $overrule = false
    ): void
    {
        if (!$this->allowToRegisterLoadedClasses && class_exists($classToOverride, false)) {
            throw new OverriddenClassAlreadyLoadedException(
                'The class: ' . $classToOverride . ' can not be overridden, because it is already loaded!');
        }

        if (!$overrule && $this->hasClassOverride($classToOverride)) {
            throw new OverriddenClassConflictException(
                'The class: ' . $classToOverride . ' is already overridden with: '
                . $this->overrideDefinitions[$classToOverride] . ' and therefore, can not be overridden again!');
        }

        $this->overrideDefinitions[$classToOverride] = $classToOverrideWith;

        if ($this->isTestMode && isset($this->autoLoader)) {
            try {
                $this->autoLoader->loadClass($classToOverride);
            } catch (Throwable $e) {
            }
        }
    }

    /**
     * Returns true if the given class can be overwritten with something else
     *
     * @param string $classToOverride The name of the class to check for
     * @param bool $withOverrule Set this to true if you want to allow overruling of the existing definition
     *
     * @return bool
     */
    public function canOverrideClass(string $classToOverride, bool $withOverrule = false): bool
    {
        if (class_exists($classToOverride, false)) {
            return false;
        }

        if (!isset($this->overrideDefinitions[$classToOverride])) {
            return true;
        }

        if ($withOverrule) {
            return true;
        }

        return false;
    }

    /**
     * Returns true if the class with the given name is registered as override
     *
     * @param string $classToOverride The name of the class to check for
     *
     * @return bool
     */
    public function hasClassOverride(string $classToOverride): bool
    {
        return isset($this->overrideDefinitions[$classToOverride]);
    }

    /**
     * Builds the list of override dependencies that must be loaded as well, when a specific class is required
     *
     * @param string $className The name of the class to resolve
     *
     * @return array|null
     */
    public function getClassStack(string $className): ?array
    {
        if (!$this->hasClassOverride($className)) {
            return null;
        }

        // Resolve the dependency list
        $classToOverrideWith = $this->overrideDefinitions[$className];
        $stack = [$className => $classToOverrideWith];
        for ($i = 0; $i < 100; $i++) {
            if (isset($this->overrideDefinitions[$classToOverrideWith])) {
                $tmp = $classToOverrideWith;
                $classToOverrideWith = $this->overrideDefinitions[$classToOverrideWith];
                $stack[$tmp] = $classToOverrideWith;
            } else {
                break;
            }
        }

        return $stack;
    }

    /**
     * Returns a list of all classes that are considered "not-prealoadable" by the PHP preload feature.
     * Because if they would be preloaded, this would break our internal logic and provide the classes
     * before we want them to be provided
     *
     * @return array
     */
    public function getNotPreloadableClasses(): array
    {
        return array_unique(
            array_merge(
                array_keys($this->overrideDefinitions),
                array_values($this->overrideDefinitions)
            )
        );
    }
}