<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Test\Fixture;


final class FixturePrivateObject implements FixtureInterface
{
    final private const CONST = 'const';

    private static int $fooStat = 0;

    private $var;

    public string $publicValue = 'Hit me!';

    private int $foo = 1;
    private
    int
        $fooNotInitialized;
    protected string $bar = 'test';
    private FixtureOverrideClass|FixtureSimpleClass $class;
    private string $constVal = self::CONST;

    private static array $staticProperty = [self::CONST];

    public function __construct(protected int $param, private string $constructorParam, bool $paramB)
    {

    }

    protected function testProt(): string
    {
        return 'test';
    }

    private
    final
    function testPriv(int $val): int
    {
        return $val;
    }

    private static function testPrivStat(): int
    {
        return 5;
    }

    /**
     * @return FixtureOverrideClass|FixtureSimpleClass|FixturePrivateObject
     */
    private function methodWithUnionReturn(): FixtureOverrideClass|FixtureSimpleClass|self
    {
        return $this->class;
    }

    private final function methodWithPrivateConstructorAccessor(): string
    {
        return $this->constructorParam;
    }

    private final static function finalStatic1()
    {
        ?>
        hello world
        <?php
    }

    private static final function finalStatic2()
    {
        return [self::CONST, 'bar' => self::CONST];
    }

    final function pubFuncWithoutFinal()
    {
        self::internal();
        FixturePrivateObject::$fooStat = 2;
        \Neunerlei\Lockpick\Test\Fixture\FixturePrivateObject::$fooStat = 2;
    }

    private function getAnonymousClass(): object
    {
        return new class implements FixtureInterface {
            protected function test()
            {

            }

            private function test()
            {

            }
        };
    }

    /**
     * @return self
     */
    final protected function getSelf(): self
    {
        return $this;
    }

    /**
     * @return FixturePrivateObject|self|int
     */
    final protected function getSelfOrElse(): FixturePrivateObject|self|int
    {
        return $this;
    }

    /**
     * @return FixturePrivateObject
     */
    final static private function internal(): FixturePrivateObject
    {
    }

    /**
     * @return \Neunerlei\Lockpick\Test\Fixture\FixturePrivateObject
     */
    final static private function internalFull(): FixturePrivateObject
    {
    }
}