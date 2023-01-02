<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Override\Event;


/**
 * Class ClassOverrideContentFilterEvent
 *
 * Can be used to modify the content of both the class copy and the class alias before the files are dumped
 */
class ClassOverrideContentFilterEvent extends AbstractClassOverrideEvent
{

    /**
     * The current class in the stack that should be overwritten
     *
     * @var string
     */
    protected string $classNameToOverride;

    /**
     * The name of the generated copy of the class
     *
     * @var string
     */
    protected string $copyClassName;

    /**
     * The first name in the stack that is overwritten
     *
     * @var string
     */
    protected string $initialClassName;

    /**
     * The last name in the stack that is overwritten
     *
     * @var string
     */
    protected string $finalClassName;

    /**
     * The content of the current copy that is created
     *
     * @var string
     */
    protected string $cloneContent;

    /**
     * The content of the class alias that is created to link the new class with the actual class name
     *
     * @var string
     */
    protected string $aliasContent;

    public function __construct(
        string $classNameToOverride,
        string $copyClassName,
        string $initialClassName,
        string $finalClassName,
        string $cloneContent,
        string $aliasContent
    )
    {
        $this->classNameToOverride = $classNameToOverride;
        $this->copyClassName = $copyClassName;
        $this->initialClassName = $initialClassName;
        $this->finalClassName = $finalClassName;
        $this->cloneContent = $cloneContent;
        $this->aliasContent = $aliasContent;
    }

    public function getCloneContent(): string
    {
        return $this->cloneContent;
    }

    public function setCloneContent(string $cloneContent): ClassOverrideContentFilterEvent
    {
        $this->cloneContent = $cloneContent;

        return $this;
    }

    public function getAliasContent(): string
    {
        return $this->aliasContent;
    }

    public function setAliasContent(string $aliasContent): ClassOverrideContentFilterEvent
    {
        $this->aliasContent = $aliasContent;

        return $this;
    }

    public function getClassNameToOverride(): string
    {
        return $this->classNameToOverride;
    }

    public function getCopyClassName(): string
    {
        return $this->copyClassName;
    }

    public function getInitialClassName(): string
    {
        return $this->initialClassName;
    }

    public function getFinalClassName(): string
    {
        return $this->finalClassName;
    }
}