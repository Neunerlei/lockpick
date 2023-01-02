<?php
declare(strict_types=1);


namespace Neunerlei\Lockpick\Test\Fixture;


use Psr\EventDispatcher\EventDispatcherInterface;

class DummyEventDispatcher implements EventDispatcherInterface
{
    protected array $listeners = [];

    public function addListener(string $eventName, callable $callback): void
    {
        $this->listeners[$eventName][] = $callback;
    }

    /**
     * @inheritDoc
     */
    public function dispatch(object $event)
    {
        foreach (($this->listeners[get_class($event)] ?? []) as $callback) {
            $callback($event);
        }
    }

}