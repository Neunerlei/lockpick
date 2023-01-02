# Lock picks

## TLDR

This is a package that allows you to disable all locks that another author might impose on code you use in your project.
It contains some tools that I use for years now and work quite well.

## WARNING

- Expect there to be a LOT of inflection happening
- If you break open third-party code be sure that **YOU KNOW** what you are doing and the code might change and so
  your code needs to be flexible enough not to break.

## Rambling

While I understand why principles like SOLID exist and why you can easily get in trouble if you start to modify
third-party code. In a day-to-day basis, where you need to find solutions `private` and `final` are two words that bring
me to the brink of cringe every time I see them. Making something private AND final in code other developers might use
is like locking in a room without lights, windows and a door they could use to do their job. If you as an author use
`protected` and someone modifies that code, without it being clearly marked as "@api" it should be fairly clear that
there might be dragons when you start to fiddle with the internals. But, please, please guys and gals all around,
don't take away the option to fix a bug you did, or extend a feature you did.

## What's in the box

## Class lock-pick

The `Neunerlei\Lockpick\Util\ClassLockpick` class is minimal invasive tool to do stuff with objects that are locked for
extension. For example, if there is a private property you want to get the data from, or call a private method.
The usage is simple and comes with minimal overhead:

```php
<?php

use Neunerlei\Lockpick\Util\ClassLockpick;

class LockedClass {
    private static int $staticProperty = 0;
    private int $property = 1;
    
    private function foo(string $name): string{
        return sprintf('hello %s', $name);
    }
    
    private static function staticFoo(): string {
        return 'I am static!';
    }
}

$i = new LockedClass();

// Create a lock-pick instance for the class
$lp = new ClassLockpick($i);

// Check if the property exists
if($lp->hasProperty('property')){
    // Use one of the utility methods
    echo $lp->getPropertyValue('property'); // 1
    
    // You can set the value in the same way
    $lp->setPropertyValue('property', 3);
    
    // Alternatively you can use the "magic" approach
    echo $lp->property; // 3
    $lp->property = 5;
    echo $lp->property; // 5
}

// You can do the same for methods
if($lp->hasMethod('foo')){
    // You can also run parameters in it
    echo $lp->runMethod('foo', ['bar']); // 'hello bar'
    
    // You can also apply a bit of sugar here
    echo $lp->foo('bar'); // 'hello bar'
}

// Of course, this also works for statics
if(ClassLockpick::hasStaticProperty(LockedClass::class, 'staticProperty')){
    echo ClassLockpick::getStaticPropertyValue(LockedClass::class, 'staticProperty'); // 0;
    ClassLockpick::setStaticPropertyValue(LockedClass::class, 'staticProperty' 2);
    echo ClassLockpick::getStaticPropertyValue(LockedClass::class, 'staticProperty'); // 2;
}

// This will work for methods as well
if(ClassLockpick::hasStaticMethod(LockedClass::class, 'staticFoo')){
    echo ClassLockpick::runStaticMethod(LockedClass::class, 'staticFoo'); // 'I am static'
}
```

## Class Overrider

Now, let's take a look at the bigger guns, shall we? How about cases where you need/want to extend
the functionality of a class, or hook into an existing process, without forking the whole package,
but everything is `final` and `private`. In that case the only solution will be to modify
the actual code of the class in order to break them open. The class overrider is a runtime tool
that lets you do exactly that; override classes in an automagical way.

### Installation

The installation is rather easy, but you have to know the application you work with.

1. You need to know a location where we can securely store compiled php classes. (Writable directory outside the
   docroot)
2. You want to configure your overrides as soon as possible in the lifecycle of your application in order to get the
   most out of this feature.
3. Your application needs to run using [composer](https://getcomposer.org/)

For example in a Symfony application I would suggest doing this at the TOP of the "boot" method in your Kernel.
As a storage location I would suggest the app's "var" directory and preferably in a sub-directory
like `/var/classOverrides`

```php
<?php

use Neunerlei\Lockpick\Override\ClassOverrider;

// First you need to register the class overrider for your application
ClassOverrider::init(
    // You need to provide an autoloader, which can be either done completely manually,
    // or using the "makeAutoLoaderByStoragePath" factory.
    ClassOverrider::makeAutoLoaderByStoragePath(
        // The first parameter is the absolute path to the directory where we can put some PRIVATE PHP FILEs
        // This directory MUST be writable by the webserver, but MUST NEVER be readable to the outside world!
        __DIR__.'/var/classOverrides',
        // The second parameter is the composer autoloader, which you can acquire by "requiring" the
        // autoload.php file from the vendor directory. There is no harm in doing this multiple times,
        // so it will work fine, even if composer was already loaded earlier. 
        require __DIR__ . '/../vendor/autoload.php'
    )
);
```

If you run your application (or refresh the page), and everything still works without any issue, you are good to go.

### Usage

After you installed the lass overrider in your application you have to consider two rules:

1. make sure the class you want to overwrite is not already loaded via auto-loader or direct include
2. your class has to be loadable using composer's auto-load functionality

If your class matches the criteria you can call the overrider in your code like so.
Imagine you have a class like this, which comes from a third-party
package:

```php
<?php
namespace ForeignVendor\ForeignNamespace;
final class TargetClass {
    public function foo(){
        // Does fancy stuff
    }

    private function privateBar() {
        // Returns interesting stuff
    }
}
```

To extend the class, you first have to create a new class somewhere in your code, that contains your extension.
The location and namespace is up to you, the only think you need to do is to extend the special parent class.

The parent class WILL BE (after you did all the steps configured) GENERATED for you based on the name of
the class you want to override.

So for our example: `ForeignVendor\ForeignNamespace\TargetClass`,
the generated class will be called `ForeignVendor\ForeignNamespace\LockpickClassOverrideTargetClass` instead.
The `LockpickClassOverride` part will be the prefix of every generated class.

IMPORTANT: The class will probably not exist when you create your extension class, so you can't rely on
your IDE's autocompletion there.

```php
<?php
namespace YourVendor\YourNamespace;
use ForeignVendor\ForeignNamespace\LockpickClassOverrideTargetClass;
class ExtendedTargetClass extends LockpickClassOverrideTargetClass {
    public function foo(){
        // Do YOUR fancy stuff
        parent::foo();
        // Use private members of the parent without problems
        $this->privateBar();
    }
}
```

After that you can call the class overrider somewhere in your code, BEFORE the actual implementation is autoloaded.
I would suggest configuring the overrider near-ish to where you called `ClassOverrider::init`.

```php
<?php
use Neunerlei\Lockpick\Override\ClassOverrider;

use ForeignVendor\ForeignNamespace\TargetClass;
use YourVendor\YourNamespace\ExtendedTargetClass;

ClassOverrider::registerOverride(TargetClass::class, ExtendedTargetClass::class);
```

Now the generator will create a copy of the `TargetClass` that under the name `LockpickClassOverrideTargetClass`.
Your implementation will extend the class copy as a child class.
With that, you can overwrite the parent implementation or extend existing members like you would usually:

1. All private members of the parent class (methods and properties) are converted into protected members so that you can
   extend them, too.
2. Your implementation in class ExtendedTargetClass will be aliased with the original class name.

With that in place, every part of the code will now use your implementation instead of the original class.

### Installation part 2 - or how to get rid of the copies

Because we are generating actual files on the drive and reuse them over and over to avoid performance issues,
you probably want to delete the built files whenever they were updated.

I would suggest to do that either on composer install/update or whenever your framework clears its caches.

After you initialized the Overrider, call the `ClassOverrider::flushStorage();` method to remove all compiled class
copies.

### Caveats

- The extended class is a modified copy of the original class, so your IDE shift-click will not work as expected.
- You will see the extended classes and the copied class names instead of the original class in logs and backtraces.
- Only works for classes that follow the PSR-4 guideline with a single class per file

## Postcardware

You're free to use this package, but if it makes it to your production environment I highly appreciate you sending me a
postcard from your hometown, mentioning
which of our package(s) you are using.

You can find my address [here](https://www.neunerlei.eu/).

Thank you :D 