<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Util;


use InvalidArgumentException;
use ReflectionObject;

class ClassLockpick
{
    protected object $instance;
    protected ReflectionObject $reflector;

    public function __construct(object $instance)
    {
        $this->reflector = new ReflectionObject($instance);

        if (!$this->reflector->isUserDefined()) {
            throw new InvalidArgumentException('You can only pick locks of user defined classes!');
        }

        $this->instance = $instance;
    }

    /**
     * Allows you to update the value of a single property in the given instance
     *
     * @param string $name The name of the property you want to modify
     * @param mixed $value The new value of the property to be set
     * @return $this
     */
    public function setPropertyValue(string $name, mixed $value): self
    {
        if (!$this->reflector->hasProperty($name)) {
            if ($this->reflector->hasMethod('__set')) {
                $this->reflector->getMethod('__set')->invoke($this->instance, $name, $value);
            }

            throw new InvalidArgumentException(sprintf(
                'The property: "%s" in class: "%s" does not exist!',
                $name,
                get_class($this->instance)
            ));
        }

        $prop = $this->reflector->getProperty($name);

        if (!$prop->isPublic()) {
            $prop->setAccessible(true);
        }

        $prop->setValue($this->instance, $value);

        return $this;
    }

    /**
     * Tries to retrieve the value of a property with the given name.w
     * @param string $name The name of the property to find
     * @param mixed|null $default A default value to return, if either the property does not exist,
     *                            or is not yet initialized
     * @return mixed
     */
    public function getPropertyValue(string $name, mixed $default = null): mixed
    {
        if (!$this->reflector->hasProperty($name)) {
            if ($this->reflector->hasMethod('__get')) {
                return $this->reflector->getMethod('__get')->invoke($this->instance, $name);
            }

            return $default;
        }

        $prop = $this->reflector->getProperty($name);

        if (!$prop->isPublic()) {
            $prop->setAccessible(true);
        }

        try {
            return $prop->getValue($this->instance);
        } catch (\Throwable) {
            return $default;
        }
    }

    /**
     * Checks if the target instance has a property with the given name.
     * Can also check if there are magic methods that can resolve the value for the property
     *
     * @param string $name The name of the property to access
     * @param bool $tryMagicMethods If set to true, magic __isset and __get methods are checked, too.
     * @return bool
     */
    public function hasProperty(string $name, bool $tryMagicMethods = false): bool
    {
        if ($this->reflector->hasProperty($name)) {
            // If we have the property but is static, we consider it as not being part of this object
            return !($this->reflector->getProperty($name)->isStatic());
        }

        if ($tryMagicMethods) {
            if ($this->reflector->hasMethod('__isset')) {
                return $this->reflector->getMethod('__isset')->invoke($this->instance, $name);
            }

            if ($this->reflector->hasMethod('__get')) {
                try {
                    return !empty($this->reflector->getMethod('__get')->invoke($this->instance, $name));
                } catch (\Throwable) {
                    return false;
                }
            }
        }

        return false;
    }

    /**
     * Executes the method on the current instance and returns it result
     * @param string $name The name of the method to execute
     * @param array|null $args A list of arguments to pass to the method
     * @return mixed
     */
    public function runMethod(string $name, ?array $args = null): mixed
    {
        $args = $args ?? [];

        if (!$this->reflector->hasMethod($name)) {
            if ($this->reflector->hasMethod('__call')) {
                $this->reflector->getMethod('__call')->invokeArgs($this->instance, $args);
            }

            throw new InvalidArgumentException(sprintf(
                'The method: "%s" in class: "%s" does not exist!',
                $name,
                get_class($this->instance)
            ));
        }

        $method = $this->reflector->getMethod($name);

        if (!$method->isPublic()) {
            $method->setAccessible(true);
        }

        return $method->invokeArgs($this->instance, $args);
    }

    /**
     * Returns true if the current instance has a method with the given name, false if not
     * @param string $name The name of the method to check
     * @return bool
     */
    public function hasMethod(string $name): bool
    {
        return $this->reflector->hasMethod($name) && !$this->reflector->getMethod($name)->isStatic();
    }

    /**
     * @inheritDoc
     */
    public function __isset(string $name): bool
    {
        if (!$this->hasProperty($name)) {
            return false;
        }

        $prop = $this->reflector->getProperty($name);
        $prop->setAccessible(true);
        return $prop->isInitialized($this->instance);
    }

    /**
     * Access class property
     *
     * @param string $name The name of the property to access
     *
     * @return mixed The property's value
     */
    public function __get(string $name)
    {
        return $this->getPropertyValue($name);
    }

    /**
     * Set a value of a property inside the subject
     *
     * @param string $name The name of the property to set
     * @param mixed $value The value to set for the given property
     */
    public function __set(string $name, mixed $value)
    {
        return $this->setPropertyValue($name, $value);
    }

    /**
     * Executes a given method of the subject
     *
     * @param string $name The name of the method to execute
     * @param array $arguments The arguments to pass to the method
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return $this->runMethod($name, $arguments);
    }

    /**
     * Checks if the given class has a static property with the given name
     * @param string $className
     * @param string $property
     * @return bool
     */
    public static function hasStaticProperty(string $className, string $property): bool
    {
        $ref = (new \ReflectionClass($className));
        if (!$ref->hasProperty($property)) {
            return false;
        }
        if (!$ref->getProperty($property)->isStatic()) {
            return false;
        }
        return true;
    }

    /**
     * Sets the value of a static property
     *
     * @param string $className
     * @param string $property
     * @param           $value
     */
    public static function setStaticPropertyValue(string $className, string $property, $value): void
    {
        $ref = (new \ReflectionClass($className));
        if (!$ref->hasProperty($property)) {
            throw new InvalidArgumentException(sprintf(
                'The property: "%s" in class: "%s" does not exist!',
                $property,
                $className
            ));
        }

        $prop = $ref->getProperty($property);

        if (!$prop->isStatic()) {
            throw new InvalidArgumentException(sprintf(
                'The property: "%s" in class: "%s" is not static!',
                $property,
                $className
            ));
        }

        $prop->setAccessible(true);
        $prop->setValue(null, $value);
    }

    /**
     * Returns the value of a static property of a class
     *
     * @param string $className
     * @param string $property
     * @param mixed|null $default
     * @return mixed
     */
    public static function getStaticPropertyValue(string $className, string $property, mixed $default = null): mixed
    {
        $ref = (new \ReflectionClass($className));
        if (!$ref->hasProperty($property)) {
            return $default;
        }

        $prop = $ref->getProperty($property);

        if (!$prop->isStatic()) {
            return $default;
        }

        $prop->setAccessible(true);

        try {
            return $prop->getValue();
        } catch (\Throwable) {
            return $default;
        }
    }

    /**
     * Checks if the provided class has a statics method wit the given name
     * @param string $className
     * @param string $method
     * @return bool
     */
    public static function hasStaticMethod(string $className, string $method): bool
    {
        $ref = (new \ReflectionClass($className));
        if (!$ref->hasMethod($method)) {
            return false;
        }
        if (!$ref->getMethod($method)->isStatic()) {
            return false;
        }
        return true;
    }

    /**
     * Executes a static method of a class and returns its return value
     *
     * @param string $className
     * @param string $method
     * @param array|null $args
     *
     * @return mixed
     */
    public static function runStaticMethod(string $className, string $method, ?array $args = null): mixed
    {
        $ref = (new \ReflectionClass($className));
        if (!$ref->hasMethod($method)) {
            if ($ref->hasMethod('__callStatic')) {
                $ref->getMethod('__callStatic')->invokeArgs(null, $args ?? []);
            }

            throw new InvalidArgumentException(sprintf(
                'The method: "%s" in class: "%s" does not exist!',
                $method,
                $className
            ));
        }

        $methodRef = $ref->getMethod($method);
        $methodRef->setAccessible(true);

        if (!$methodRef->isStatic()) {
            throw new InvalidArgumentException(sprintf(
                'The method: "%s" in class: "%s" is not static!',
                $method,
                $className
            ));
        }

        $args = $args ?? [];

        return $methodRef->invoke(null, ...$args);
    }
}