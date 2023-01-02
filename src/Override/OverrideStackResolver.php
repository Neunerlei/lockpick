<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Override;


use Neunerlei\FileSystem\Path;
use Neunerlei\Inflection\Inflector;
use Neunerlei\Lockpick\Override\Driver\IoDriverInterface;
use Neunerlei\Lockpick\Override\Event\ClassOverrideContentFilterEvent;
use Neunerlei\Lockpick\Override\Event\ClassOverrideStackFilterEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class OverrideStackResolver
{
    protected IoDriverInterface $driver;
    protected \Closure $codeGeneratorFactory;
    protected ?EventDispatcherInterface $eventDispatcher;
    protected ?CodeGenerator $codeGenerator;

    /**
     * Internal list to store the list of files to be included by the autoloader
     *
     * @var array
     */
    protected array $includeList;

    /**
     * The list of already resolved aliases to avoid multiple executions
     *
     * @var array
     */
    protected array $resolvedAliasMap = [];

    /**
     * True if the script is executed in phpunit
     *
     * @var bool
     */
    protected bool $isTestMode = false;

    public function __construct(IoDriverInterface $driver, \Closure $codeGeneratorFactory, ?EventDispatcherInterface $eventDispatcher = null)
    {
        $this->driver = $driver;
        $this->codeGeneratorFactory = $codeGeneratorFactory;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Allows you to update the internal event dispatcher instance
     * @param EventDispatcherInterface $eventDispatcher
     * @return void
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Used to toggle the internal test mode flag
     *
     * @param bool $isTestMode
     */
    public function setTestMode(bool $isTestMode): void
    {
        $this->isTestMode = $isTestMode;
    }

    /**
     * Returns the instance of the io driver used for file system operations
     * @return IoDriverInterface
     */
    public function getIoDriver(): IoDriverInterface
    {
        return $this->driver;
    }

    /**
     * Resolves the given stack of override dependencies, by creating the required override files
     * and automatically including them from their temporary sources.
     * The result is the definition for the TYPO3 ClassAliasMap
     *
     * @param array $stack
     *
     * @return array
     * @throws \JsonException
     */
    public function resolve(array $stack): array
    {
        if (isset($this->eventDispatcher)) {
            $this->eventDispatcher->dispatch(
                ($e = new ClassOverrideStackFilterEvent($stack))
            );
            $stack = $e->getStack();
        }

        $cacheKey = md5(json_encode($stack, JSON_THROW_ON_ERROR) . '-' . $this->isTestMode);
        if (isset($this->resolvedAliasMap[$cacheKey])) {
            return $this->resolvedAliasMap[$cacheKey];
        }

        reset($stack);
        $initialClassName = key($stack);
        $finalClassName = end($stack);

        $this->includeList = [];
        foreach ($stack as $classToOverride => $classToOverrideWith) {
            $this->resolveStackEntry((string)$initialClassName, (string)$finalClassName, $classToOverride, $classToOverrideWith);
        }

        foreach ($this->includeList as $aliasFilename) {
            $this->driver->includeFile($aliasFilename);
        }
        $this->includeList = [];

        return $this->resolvedAliasMap[$cacheKey] = [$finalClassName => $initialClassName];
    }

    /**
     * Resolves a single stack entry by defining the required include files,
     * and creating a copy of the class if required
     *
     * @param string $initialClassName
     * @param string $finalClassName
     * @param string $classToOverride
     * @param string $classToOverrideWith
     */
    protected function resolveStackEntry(
        string $initialClassName,
        string $finalClassName,
        string $classToOverride,
        string $classToOverrideWith
    ): void
    {
        $basename = Inflector::toFile($classToOverride);
        $cloneFilename = $basename . '-clone.php';
        $aliasFilename = $basename . '.php';
        $this->includeList[] = $cloneFilename;
        $this->includeList[] = $aliasFilename;

        if (!$this->driver->hasFile($aliasFilename) || !$this->driver->hasFile($cloneFilename)) {
            $this->generateClassCopy(
                $classToOverride, $classToOverrideWith,
                $initialClassName, $finalClassName,
                $cloneFilename, $aliasFilename);
        }
    }

    /**
     * Internal helper to actually generate the content of a class override. {@see resolveStackEntry} for how
     * it should be used.
     *
     * @param string $classToOverride
     * @param string $classToOverrideWith
     * @param string $initialClassName
     * @param string $finalClassName
     * @param string $cloneFilename
     * @param string $aliasFilename
     * @return void
     */
    protected function generateClassCopy(string $classToOverride, string $classToOverrideWith,
                                         string $initialClassName, string $finalClassName,
                                         string $cloneFilename, string $aliasFilename): void
    {
        $namespace = Path::classNamespace($classToOverride);
        $copyClassName = ClassOverrider::CLASS_COPY_PREFIX . Path::classBasename($classToOverride);
        $copyClassFullName = ltrim($namespace . '\\' . $copyClassName, '\\');

        $codeGenerator = $this->getCodeGenerator();
        $cloneContent = $codeGenerator->getClassCloneContentOf(
            $classToOverride, $copyClassName);
        $aliasContent = $codeGenerator->getClassAliasContent(
            $classToOverride, $classToOverrideWith, $finalClassName, $copyClassFullName);

        if (isset($this->eventDispatcher)) {
            $e = new ClassOverrideContentFilterEvent(
                $classToOverride,
                $copyClassName,
                $initialClassName,
                $finalClassName,
                $cloneContent,
                $aliasContent
            );

            $this->eventDispatcher->dispatch($e);
            $cloneContent = $e->getCloneContent();
            $aliasContent = $e->getAliasContent();
        }

        $this->driver->setFileContent($cloneFilename, $cloneContent);
        $this->driver->setFileContent($aliasFilename, $aliasContent);
    }

    /**
     * Internal getter to resolve the code generator lazily
     *
     * @return CodeGenerator
     */
    public function getCodeGenerator(): CodeGenerator
    {
        $c = $this->codeGenerator ?? ($this->codeGenerator = ($this->codeGeneratorFactory)());

        $c->setTestMode($this->isTestMode);

        return $c;
    }
}