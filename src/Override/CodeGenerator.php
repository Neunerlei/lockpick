<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Override;


use Composer\Autoload\ClassLoader;
use Neunerlei\FileSystem\Fs;
use Neunerlei\FileSystem\Path;
use Neunerlei\Lockpick\Override\CodeManipulation\ClassAlias\BaseCodeProvidingManipulator;
use Neunerlei\Lockpick\Override\CodeManipulation\ClassClone\ClassRenamingManipulator;
use Neunerlei\Lockpick\Override\CodeManipulation\ClassClone\FinalModifierManipulator;
use Neunerlei\Lockpick\Override\CodeManipulation\ClassClone\HeaderInjectionManipulator;
use Neunerlei\Lockpick\Override\CodeManipulation\ClassClone\PrivateToProtectedManipulator;
use Neunerlei\Lockpick\Override\CodeManipulation\ClassClone\SelfReferenceManipulator;
use Neunerlei\Lockpick\Override\CodeManipulation\CodeManipulatorInterface;
use Neunerlei\Lockpick\Override\Exception\ComposerCouldNotResolveTargetClassException;

class CodeGenerator
{
    protected ClassLoader $composerClassLoader;

    /**
     * @var CodeManipulatorInterface[]
     */
    protected array $classCloneManipulators = [];

    /**
     * @var CodeManipulatorInterface[]
     */
    protected array $classAliasManipulators = [];

    public function __construct(ClassLoader $composerClassLoader)
    {
        $this->composerClassLoader = $composerClassLoader;

        $this->addClassCloneManipulator(new FinalModifierManipulator());
        $this->addClassCloneManipulator(new PrivateToProtectedManipulator());
        $this->addClassCloneManipulator(new ClassRenamingManipulator());
        $this->addClassCloneManipulator(new SelfReferenceManipulator());
        $this->addClassCloneManipulator(new HeaderInjectionManipulator());

        $this->addClassAliasManipulator(new BaseCodeProvidingManipulator());
    }

    /**
     * Returns the list of all registered code manipulators, responsible for modifying the class clone code
     * @return CodeManipulatorInterface[]
     */
    public function getClassCloneManipulators(): array
    {
        return $this->classCloneManipulators;
    }

    /**
     * Sets the list of all registered code manipulators, responsible for modifying the class clone code
     * @param CodeManipulatorInterface[] $manipulators A list of code manipulator instances
     * @return $this
     */
    public function setClassCloneManipulators(array $manipulators): self
    {
        $this->classCloneManipulators = [];
        array_map([$this, 'addClassCloneManipulator'], $manipulators);
        return $this;
    }

    /**
     * Adds a new code manipulator to the list, responsible for modifying the class clone code
     * @param CodeManipulatorInterface $manipulator
     * @return $this
     */
    public function addClassCloneManipulator(CodeManipulatorInterface $manipulator): self
    {
        $this->classCloneManipulators[] = $manipulator;
        return $this;
    }

    /**
     * Returns the list of all registered code manipulators, responsible for generating/modifying the class alias code
     * @return CodeManipulatorInterface[]
     */
    public function getClassAliasManipulators(): array
    {
        return $this->classAliasManipulators;
    }

    /**
     * Adds a new code manipulator to the list, responsible for generating/modifying the class alias code
     * @param CodeManipulatorInterface[] $manipulators A list of code manipulator instances
     * @return $this
     */
    public function setClassAliasManipulators(array $manipulators): self
    {
        $this->classAliasManipulators = [];
        array_map([$this, 'addClassAliasManipulator'], $manipulators);
        return $this;
    }

    /**
     * Adds a new code manipulator to the list, responsible for generating/modifying the class alias code
     * @param CodeManipulatorInterface $manipulator
     * @return $this
     */
    public function addClassAliasManipulator(CodeManipulatorInterface $manipulator): self
    {
        $this->classAliasManipulators[] = $manipulator;
        return $this;
    }

    /**
     * Generates the class alias file content and returns it
     *
     * @param string $classToOverride
     * @param string $classToOverrideWith
     * @param string $finalClassName
     * @param string $copyClassFullName
     *
     * @return string
     */
    public function getClassAliasContent(
        string $classToOverride,
        string $classToOverrideWith,
        string $finalClassName,
        string $copyClassFullName
    ): string
    {
        $context = [
            'classToOverride' => $classToOverride,
            'classToOverrideNamespace' => Path::classNamespace($classToOverride),
            'classToOverrideBaseName' => Path::classBasename($classToOverride),
            'classToOverrideWith' => $classToOverrideWith,
            'copyClassName' => $copyClassFullName,
            'finalClassName' => $finalClassName
        ];

        $content = '';

        foreach ($this->classAliasManipulators as $manipulator) {
            $content = $manipulator->apply($content, CodeManipulatorInterface::TYPE_CLASS_ALIAS, $context);
        }

        return $content;
    }

    /**
     * This internal helper is used to read the source code of a given class, and create a copy out of it.
     * The copy has a unique name and all references, like return types and type hints will be replaced by said, new
     * name.
     *
     * @param string $classToOverride The real name of the class to create a copy of (including the namespace)
     * @param string $copyClassName The new name of the class after the copy took place (including the namespace)
     *
     * @return string
     */
    public function getClassCloneContentOf(string $classToOverride, string $copyClassName): string
    {
        // Resolve the source file
        $classToOverrideFile = $this->composerClassLoader->findFile($classToOverride);
        if ($classToOverrideFile === false) {
            throw new ComposerCouldNotResolveTargetClassException(
                'Could not create a clone of class: ' . $classToOverride
                . ' because Composer could not resolve it\'s filename!');
        }

        $content = $this->readSource($classToOverrideFile);

        $context = [
            'classToOverride' => $classToOverride,
            'classToOverrideNamespace' => Path::classNamespace($classToOverride),
            'classToOverrideBaseName' => Path::classBasename($classToOverride),
            'classToOverrideFile' => $classToOverrideFile,
            'copyClassName' => $copyClassName,
            'originalCode' => $content,
        ];

        foreach ($this->classCloneManipulators as $manipulator) {
            $content = $manipulator->apply($content, CodeManipulatorInterface::TYPE_CLASS_CLONE, $context);
        }

        return $content;
    }

    /**
     * Reads the source of a class as a string
     *
     * @param string $overrideSourceFile The file which contains the class
     *
     * @return string
     */
    protected function readSource(string $overrideSourceFile): string
    {
        return Fs::readFile($overrideSourceFile);
    }
}