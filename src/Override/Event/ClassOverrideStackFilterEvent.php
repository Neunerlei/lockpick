<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Override\Event;


/**
 * Class ClassOverrideStackFilterEvent
 * Can be used to filter the class override generator stack right before the override is build
 */
class ClassOverrideStackFilterEvent extends AbstractClassOverrideEvent
{
    protected array $stack;

    public function __construct(array $stack)
    {
        $this->stack = $stack;
    }

    /**
     * Returns the list of steps that are required to resolve a class through
     * all it's overrides.
     *
     * @return array
     */
    public function getStack(): array
    {
        return $this->stack;
    }

    /**
     * Can be used to set the list of steps that are required to resolve a class through
     * all it's overrides.
     *
     * @param array $stack
     *
     * @return ClassOverrideStackFilterEvent
     */
    public function setStack(array $stack): ClassOverrideStackFilterEvent
    {
        $this->stack = $stack;

        return $this;
    }
}