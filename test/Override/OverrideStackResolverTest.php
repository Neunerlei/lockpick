<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Test\Override;


use Neunerlei\FileSystem\Path;
use Neunerlei\Inflection\Inflector;
use Neunerlei\Lockpick\Override\ClassOverrider;
use Neunerlei\Lockpick\Override\CodeGenerator;
use Neunerlei\Lockpick\Override\Driver\DefaultIoDriver;
use Neunerlei\Lockpick\Override\Driver\IoDriverInterface;
use Neunerlei\Lockpick\Override\Event\ClassOverrideContentFilterEvent;
use Neunerlei\Lockpick\Override\Event\ClassOverrideStackFilterEvent;
use Neunerlei\Lockpick\Override\OverrideStackResolver;
use Neunerlei\Lockpick\Test\Fixture\DummyEventDispatcher;
use Neunerlei\Lockpick\Test\Fixture\FixtureExtendedOverrideClass;
use Neunerlei\Lockpick\Test\Fixture\FixtureInvalidClass;
use Neunerlei\Lockpick\Test\Fixture\FixtureNotLoadedClass;
use Neunerlei\Lockpick\Test\Fixture\FixtureOverrideClass;
use Neunerlei\Lockpick\Test\Fixture\FixtureSuperExtendedOverrideClass;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

class OverrideStackResolverTest extends TestCase
{
    protected IoDriverInterface $driver;
    protected EventDispatcherInterface $eventDispatcher;
    protected CodeGenerator $codeGenerator;

    public function testStackResolution(): void
    {
        ob_start();
        $map = $this->makeInstance()->resolve($this->getTestStack());
        ob_end_clean();

        static::assertEquals([
            FixtureSuperExtendedOverrideClass::class => FixtureNotLoadedClass::class,
        ], $map);

        foreach ($this->getExpectedFileList() as $fileName => $content) {
            static::assertTrue($this->driver->hasFile($fileName));
            static::assertEquals(
                $content,
                json_decode($this->driver->getFileContent($fileName), true, 512, JSON_THROW_ON_ERROR),
                'Failed to find an expected file in mount: ' . $fileName
            );
        }
    }

    public function testEventExecutionInStackResolution(): void
    {
        $c = 0;
        $c2 = 0;

        $i = $this->makeInstance();

        $this->eventDispatcher->addListener(ClassOverrideStackFilterEvent::class,
            function (ClassOverrideStackFilterEvent $e) use (&$c) {
                $c += 10;

                static::assertEquals([true], $e->getStack());
                $e->setStack($this->getTestStack());
            });

        $this->eventDispatcher->addListener(ClassOverrideContentFilterEvent::class,
            function (ClassOverrideContentFilterEvent $e) use (&$c, &$c2) {
                $c2++;
                $c += 100;

                $expected = $this->getExpectedEventArgs($e->getClassNameToOverride());

                static::assertEquals($expected[0], $e->getClassNameToOverride());
                static::assertEquals($expected[1], $e->getCopyClassName());
                static::assertEquals($expected[2], $e->getInitialClassName());
                static::assertEquals($expected[3], $e->getFinalClassName());
                static::assertEquals($expected[4], json_decode($e->getCloneContent(), true, 512, JSON_THROW_ON_ERROR));
                static::assertEquals($expected[5], json_decode($e->getAliasContent(), true, 512, JSON_THROW_ON_ERROR));
            });

        ob_start();
        $m1 = $i->resolve([true]);
        ob_end_clean();

        static::assertEquals(3, $c2, 'Not all expected files triggered an event');
        static::assertEquals(310, $c, 'Either not all expected files triggered an event, or the initial event was not executed');

        // Try it again -> Now everything should be cached
        ob_start();
        $m2 = $i->resolve([true]);
        ob_end_clean();

        static::assertEquals(3, $c2, 'The caching failed, and the files have been processed again');
        static::assertEquals(320, $c, 'The global caching failed, and the process was executed again');

        // Both maps should be equal now
        static::assertEquals($m1, $m2);

    }

    protected function getTestStack(): array
    {
        return [
            FixtureNotLoadedClass::class => FixtureOverrideClass::class,
            FixtureOverrideClass::class => FixtureExtendedOverrideClass::class,
            FixtureExtendedOverrideClass::class => FixtureSuperExtendedOverrideClass::class,
        ];
    }

    protected function getExpectedEventArgs(string $classToOverride): array
    {
        $basename = Inflector::toFile($classToOverride);
        $cloneFilename = $basename . '-clone.php';
        $aliasFilename = $basename . '.php';
        $files = $this->getExpectedFileList();

        switch ($classToOverride) {
            case FixtureNotLoadedClass::class:
                return [
                    FixtureNotLoadedClass::class,
                    ClassOverrider::CLASS_COPY_PREFIX . 'FixtureNotLoadedClass',
                    FixtureNotLoadedClass::class,
                    FixtureSuperExtendedOverrideClass::class,
                    $files[$cloneFilename],
                    $files[$aliasFilename],
                ];
            case FixtureOverrideClass::class:
                return [
                    FixtureOverrideClass::class,
                    ClassOverrider::CLASS_COPY_PREFIX . 'FixtureOverrideClass',
                    FixtureNotLoadedClass::class,
                    FixtureSuperExtendedOverrideClass::class,
                    $files[$cloneFilename],
                    $files[$aliasFilename],
                ];
            case FixtureExtendedOverrideClass::class:
                return [
                    FixtureExtendedOverrideClass::class,
                    ClassOverrider::CLASS_COPY_PREFIX . 'FixtureExtendedOverrideClass',
                    FixtureNotLoadedClass::class,
                    FixtureSuperExtendedOverrideClass::class,
                    $files[$cloneFilename],
                    $files[$aliasFilename],
                ];
            default:
                static::fail('There are no known event args of a class called: ' . $classToOverride);
        }
    }

    protected function getExpectedFileList(): array
    {
        $ns = Path::classNamespace(FixtureInvalidClass::class);

        return [
            'neunerlei-lockpick-test-fixture-fixturenotloadedclass-clone.php' => [
                FixtureNotLoadedClass::class,
                ClassOverrider::CLASS_COPY_PREFIX . 'FixtureNotLoadedClass',
            ],
            'neunerlei-lockpick-test-fixture-fixturenotloadedclass.php' => [
                FixtureNotLoadedClass::class,
                FixtureOverrideClass::class,
                FixtureSuperExtendedOverrideClass::class,
                $ns . '\\' . ClassOverrider::CLASS_COPY_PREFIX . 'FixtureNotLoadedClass',
            ],
            'neunerlei-lockpick-test-fixture-fixtureoverrideclass-clone.php' => [
                FixtureOverrideClass::class,
                ClassOverrider::CLASS_COPY_PREFIX . 'FixtureOverrideClass',
            ],
            'neunerlei-lockpick-test-fixture-fixtureoverrideclass.php' => [
                FixtureOverrideClass::class,
                FixtureExtendedOverrideClass::class,
                FixtureSuperExtendedOverrideClass::class,
                $ns . '\\' . ClassOverrider::CLASS_COPY_PREFIX . 'FixtureOverrideClass',
            ],
            'neunerlei-lockpick-test-fixture-fixtureextendedoverrideclass-clone.php' => [
                FixtureExtendedOverrideClass::class,
                ClassOverrider::CLASS_COPY_PREFIX . 'FixtureExtendedOverrideClass',
            ],
            'neunerlei-lockpick-test-fixture-fixtureextendedoverrideclass.php' => [
                FixtureExtendedOverrideClass::class,
                FixtureSuperExtendedOverrideClass::class,
                FixtureSuperExtendedOverrideClass::class,
                $ns . '\\' . ClassOverrider::CLASS_COPY_PREFIX . 'FixtureExtendedOverrideClass',
            ],
        ];
    }

    protected function makeInstance(): OverrideStackResolver
    {
        $this->driver = new DefaultIoDriver();
        $this->driver->flush();

        $this->eventDispatcher = new DummyEventDispatcher();

        $this->codeGenerator = $this->createMock(CodeGenerator::class);
        $this->codeGenerator->method('getClassCloneContentOf')->willReturnCallback(static function () {
            return json_encode(func_get_args(), JSON_THROW_ON_ERROR & JSON_PRETTY_PRINT);
        });
        $this->codeGenerator->method('getClassAliasContent')->willReturnCallback(static function () {
            return json_encode(func_get_args(), JSON_THROW_ON_ERROR & JSON_PRETTY_PRINT);
        });

        return new OverrideStackResolver(
            $this->driver,
            function () {
                return $this->codeGenerator;
            },
            $this->eventDispatcher
        );
    }
}