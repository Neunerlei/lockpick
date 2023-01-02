<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Test\Fixture;


final class FixtureClassWithPrivateChildren implements FixtureInterface
{
    private const CONSTANT = true;

    private $property = true;
    private bool|string $propertyWithType = true;

    private static array $staticProperty = [self::CONSTANT];

    // We test here if the "final" after the access modifier works too
    private final function func()
    {
    }

    final public function pubFunc()
    {
    }

    final function pubFuncWithoutFinal()
    {
        self::internal();
    }

    /**
     * @return FixtureClassWithPrivateChildren
     */
    final static private function internal(): FixtureClassWithPrivateChildren
    {
    }
}