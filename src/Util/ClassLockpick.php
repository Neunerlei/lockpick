<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Util;


use InvalidArgumentException;
use ReflectionClass;

class ClassLockpick
{
    protected string $className;
    protected object $instance;
    protected ReflectionClass $reflector;

    protected static array $reflectors = [];
    protected static array $definitions = [];

    public function __construct(object $instance)
    {
        $this->className = get_class($instance);
        $this->reflector = static::getReflector($this->className);

        if (!$this->reflector->isUserDefined()) {
            throw new InvalidArgumentException('You can only pick locks of user defined classes!');
        }

        $this->instance = $instance;
    }

    /**
     * Returns the list of all properties that are available in the object
     * @return array
     */
    public function getPropertyNames(): array
    {
        $id = $this->className . '-propertyNames';
        if (isset(static::$definitions[$id])) {
            return static::$definitions[$id];
        }

        $list = [];
        static::walkInheritancePath($this->reflector, 'internal', 'propertyNames',
            static function (ReflectionClass $ref) use (&$list) {
                foreach ($ref->getProperties() as $property) {
                    if ($property->isStatic()) {
                        continue;
                    }

                    $list[] = $property->getName();
                }

                return null;
            }
        );

        return static::$definitions[$id] = array_values(array_unique($list));
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
        $ref = static::getPropertyReflection($this->reflector, $name);

        if (!$ref) {
            $ref = static::getMethodReflection($this->reflector, '__set');
            if ($ref) {
                $ref->invoke($this->instance, $name, $value);
                return $this;
            }

            throw new InvalidArgumentException(sprintf(
                'The property: "%s" in class: "%s" does not exist!',
                $name,
                get_class($this->instance)
            ));
        }

        if ($ref->isStatic()) {
            throw new InvalidArgumentException(sprintf(
                'The property: "%s" in class: "%s" is considered static!',
                $name,
                get_class($this->instance)
            ));
        }

        $ref->setValue($this->instance, $value);

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
        $ref = static::getPropertyReflection($this->reflector, $name);

        if (!$ref) {
            $ref = static::getMethodReflection($this->reflector, '__get');
            if ($ref) {
                return $ref->invoke($this->instance, $name);
            }

            return $default;
        }

        if ($ref->isStatic()) {
            return $default;
        }

        try {
            return $ref->getValue($this->instance);
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
        $ref = static::getPropertyReflection($this->reflector, $name);

        if ($ref) {
            // If we have the property but is static, we consider it as not being part of this object
            return !$ref->isStatic();
        }

        if ($tryMagicMethods) {
            $ref = static::getMethodReflection($this->reflector, '__isset');
            if ($ref) {
                return $ref->invoke($this->instance, $name);
            }

            $ref = static::getMethodReflection($this->reflector, '__get');
            if ($ref) {
                try {
                    return !empty($ref->invoke($this->instance, $name));
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

        $ref = static::getMethodReflection($this->reflector, $name);

        if (!isset($ref) || $ref->isStatic()) {
            $ref = static::getMethodReflection($this->reflector, '__call');
            if ($ref) {
                return $ref->invokeArgs($this->instance, $args);
            }

            throw new InvalidArgumentException(sprintf(
                'The method: "%s" in class: "%s" does not exist!',
                $name,
                get_class($this->instance)
            ));
        }

        return $ref->invokeArgs($this->instance, $args);
    }

    /**
     * Returns true if the current instance has a method with the given name, false if not
     * @param string $name The name of the method to check
     * @return bool
     */
    public function hasMethod(string $name): bool
    {
        return !(static::getMethodReflection($this->reflector, $name)?->isStatic());
    }

    /**
     * @inheritDoc
     */
    public function __isset(string $name): bool
    {
        $ref = static::getPropertyReflection($this->reflector, $name);

        if (!$ref || $ref->isStatic()) {
            return false;
        }

        return $ref->isInitialized($this->instance);
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
     * @param string $name
     * @return bool
     */
    public static function hasStaticProperty(string $className, string $name): bool
    {
        return (bool)static::getPropertyReflection($className, $name)?->isStatic();
    }

    /**
     * Sets the value of a static property
     *
     * @param string $className
     * @param string $name
     * @param mixed $value
     */
    public static function setStaticPropertyValue(string $className, string $name, mixed $value): void
    {
        $ref = static::getPropertyReflection($className, $name);

        if (!$ref) {
            throw new InvalidArgumentException(sprintf(
                'The property: "%s" in class: "%s" does not exist!',
                $name,
                $className
            ));
        }

        if (!$ref->isStatic()) {
            throw new InvalidArgumentException(sprintf(
                'The property: "%s" in class: "%s" is not static!',
                $name,
                $className
            ));
        }

        $ref->setValue(null, $value);
    }

    /**
     * Returns the value of a static property of a class
     *
     * @param string $className
     * @param string $name
     * @param mixed|null $default
     * @return mixed
     */
    public static function getStaticPropertyValue(string $className, string $name, mixed $default = null): mixed
    {
        $ref = static::getPropertyReflection($className, $name);

        if (!$ref) {
            return $default;
        }

        if (!$ref->isStatic()) {
            return $default;
        }

        try {
            return $ref->getValue();
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
        return (bool)static::getMethodReflection($className, $method)?->isStatic();
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
        $ref = static::getMethodReflection($className, $method) ??
            static::getMethodReflection($className, '__callStatic');

        if (!$ref) {
            throw new InvalidArgumentException(sprintf(
                'The method: "%s" in class: "%s" does not exist!',
                $method,
                $className
            ));
        }

        if (!$ref->isStatic()) {
            throw new InvalidArgumentException(sprintf(
                'The method: "%s" in class: "%s" is not static!',
                $method,
                $className
            ));
        }

        $args = $args ?? [];

        return $ref->invoke(null, ...$args);
    }

    /**
     * Returns either the reflection object of a class property or null if there is none in the inheritance
     *
     * @param string|ReflectionClass $src The reflector/class name to look on
     * @param string $name The name of the property to look for
     *
     * @return \ReflectionProperty|null
     */
    protected static function getPropertyReflection(string|ReflectionClass $src, string $name): ?\ReflectionProperty
    {
        return static::walkInheritancePath($src, 'property', $name,
            static function (ReflectionClass $ref, string $name) {
                if ($ref->hasProperty($name)) {
                    $prop = $ref->getProperty($name);
                    $prop->setAccessible(true);
                    return $prop;
                }

                return null;
            }
        );
    }

    /**
     * Returns either the reflection object of a class method or null if there is none in the inheritance
     *
     * @param string|ReflectionClass $src The reflector/class name to look on
     * @param string $name The name of the method you want to look up
     *
     * @return \ReflectionMethod|null
     */
    protected static function getMethodReflection(string|ReflectionClass $src, string $name): ?\ReflectionMethod
    {
        return static::walkInheritancePath($src, 'method', $name,
            static function (ReflectionClass $ref, string $name) {
                if ($ref->hasMethod($name)) {
                    $method = $ref->getMethod($name);
                    $method->setAccessible(true);
                    return $method;
                }

                return null;
            }
        );
    }

    /**
     * Internal helper to either resolve a reflection object or use the given one
     *
     * @param string|ReflectionClass $src Either the name of a class or a reflectionClass object
     *
     * @return ReflectionClass
     */
    protected static function getReflector(string|ReflectionClass $src): ReflectionClass
    {
        if ($src instanceof ReflectionClass) {
            return $src;
        }

        return static::$reflectors[$src] = new ReflectionClass($src);
    }

    /**
     * Because private properties/classes are only resolved on the actual class, and are not
     * inherited via reflection, we have to iterate the whole inheritance tree of a class to find
     * all possible properties and methods on it.
     *
     * To avoid lag and repetitive work, we resolve either a property or a method and cache them in a local property.
     *
     * @param string|ReflectionClass $src The reflector/class name to walk through
     * @param string $type The type of the element that should be looked up e.g. "method" or "property"
     * @param string $key The "name" of the element that should be looked up
     * @param callable $walker The walker that iterates every level of the inheritance to search for the required
     *                         element. If it returns something other than NULL the walking will be stopped.
     * @return mixed|null
     */
    protected static function walkInheritancePath(
        string|ReflectionClass $src, string $type, string $key, callable $walker): mixed
    {
        $ref = static::getReflector($src);
        $id = implode('-', [
            $ref->getName(),
            $type,
            $key
        ]);

        if (isset(static::$definitions[$id])) {
            return static::$definitions[$id];
        }

        $resolved = null;
        while ($ref) {
            $resolved = $walker($ref, $key);

            if ($resolved !== null) {
                break;
            }

            $ref = $ref->getParentClass();
        }

        return static::$definitions[$id] = $resolved;
    }
}