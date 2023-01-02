<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Test\Override;


use Neunerlei\FileSystem\Path;
use Neunerlei\Lockpick\Override\CodeGenerator;
use Neunerlei\Lockpick\Override\Exception\ComposerCouldNotResolveTargetClassException;
use Neunerlei\Lockpick\Override\Exception\OverrideClassRenamingFailedException;
use Neunerlei\Lockpick\Test\Fixture\FixtureClassWithPrivateChildren;
use Neunerlei\Lockpick\Test\Fixture\FixtureInvalidClass;
use Neunerlei\Lockpick\Test\Fixture\FixtureNotLoadedClass;
use Neunerlei\Lockpick\Test\Fixture\FixtureOverrideClass;
use PHPUnit\Framework\TestCase;

class CodeGeneratorTest extends TestCase
{

    public function testAliasCodeGeneration(): void
    {
        $classToOverride = FixtureNotLoadedClass::class;
        $classToOverrideWith = FixtureOverrideClass::class;
        $copyClassFullName = '@copyClassFullName';
        $finalClassName = '@finalClassName';

        $namespace = Path::classNamespace($classToOverride);
        $baseName = Path::classBasename($classToOverride);

        $expected = <<<PHP
<?php
declare(strict_types=1);
/**
 * CLASS OVERRIDE GENERATOR - GENERATED FILE
 * This file is generated dynamically! You should not edit its contents,
 * because they will be lost as soon as the storage is cleared
 *
 * The original class can be found here:
 * @see \\$classToOverride
 *
 * The clone of the original class can be found here:
 * @see \\$copyClassFullName
 *
 * The class which is used as override can be found here:
 * @see \\$finalClassName
 */
Namespace $namespace;
if(!class_exists('\\$classToOverride', false)) {

    class $baseName
        extends \\$classToOverrideWith {}
}
PHP;

        static::assertEquals($expected, $this->makeInstance()->getClassAliasContent(
            $classToOverride, $classToOverrideWith, $finalClassName, $copyClassFullName
        ));
    }

    public function testClassCodeGeneration(): void
    {
        $classToOverride = FixtureClassWithPrivateChildren::class;
        $namespace = Path::classNamespace($classToOverride);

        $expected = <<<PHP
<?php
/**
 * CLASS OVERRIDE GENERATOR - GENERATED FILE
 * This file is generated dynamically! You should not edit its contents,
 * because they will be lost as soon as the storage is cleared
 *
 * THIS FILE IS AUTOMATICALLY GENERATED!
 *
 * This is a copy of the class: Neunerlei\Lockpick\Test\Fixture\FixtureClassWithPrivateChildren
 *
 * @see Neunerlei\Lockpick\Test\Fixture\FixtureClassWithPrivateChildren
 */
declare(strict_types=1);


namespace Neunerlei\Lockpick\Test\Fixture;


class CopyClass implements FixtureInterface
{
    protected const CONSTANT = true;

    protected \$property = true;
    private bool|string \$propertyWithType = true;

    private static array \$staticProperty = [self::CONSTANT];

    // We test here if the "final" after the access modifier works too
    protected function func()
    {
    }

    public function pubFunc()
    {
    }

    function pubFuncWithoutFinal()
    {
        static::internal();
    }

    /**
     * @return CopyClass
     */
    static protected function internal(): FixtureClassWithPrivateChildren
    {
    }
}
PHP;
        static::assertEquals($expected, $this->makeInstance()->getClassCloneContentOf(
            FixtureClassWithPrivateChildren::class, 'CopyClass'
        ));
    }

    public function testGetClassCloneContentOfFailIfClassCouldNotBeResolved(): void
    {
        $this->expectException(ComposerCouldNotResolveTargetClassException::class);
        $this->expectExceptionMessage('ould not create a clone of class: \Foo\Bar because Composer could not resolve it\'s filename!');
        $this->makeInstance()->getClassCloneContentOf('\\Foo\\Bar', 'Baz');
    }

    public function testGetClassCloneContentOfFailIfClassNameDoesNotMatchAutoLoadPath(): void
    {
        $this->expectException(OverrideClassRenamingFailedException::class);
        $this->expectExceptionMessage('Failed to rewrite the name of class: FixtureInvalidAutoLoadPathClass to: Baz when creating a copy of class: Neunerlei\Lockpick\Test\Fixture\FixtureInvalidAutoLoadPathClass');
        $ns = Path::classNamespace(FixtureInvalidClass::class);
        $this->makeInstance()->getClassCloneContentOf($ns . '\\FixtureInvalidAutoLoadPathClass', 'Baz');
    }

    protected function makeInstance(): CodeGenerator
    {
        return new CodeGenerator(require __DIR__ . '/../../vendor/autoload.php');
    }
}