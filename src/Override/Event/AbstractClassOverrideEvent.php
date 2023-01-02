<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Override\Event;


use Psr\EventDispatcher\StoppableEventInterface;

abstract class AbstractClassOverrideEvent implements StoppableEventInterface
{
    protected bool $isStopped = false;

    /**
     * Should block all other handlers after this method was called
     * @param bool $state
     * @return void
     */
    public function stopPropagation(bool $state = true): void
    {
        $this->isStopped = $state;
    }

    /**
     * @inheritDoc
     */
    public function isPropagationStopped(): bool
    {
        return $this->isStopped;
    }
}