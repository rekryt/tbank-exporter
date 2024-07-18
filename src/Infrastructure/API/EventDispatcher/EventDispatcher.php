<?php

namespace TBank\Infrastructure\API\EventDispatcher;

use TBank\App\Event\EventInterface;
use function Amp\async;

/**
 * @see EventDispatcherTest
 */
final class EventDispatcher implements EventDispatcherInterface {
    /**
     * @param ListenerProviderInterface $provider
     */
    public function __construct(private readonly ListenerProviderInterface $provider = new ListenerProvider()) {
    }

    /**
     * @param EventInterface $event
     * @return EventInterface
     */
    public function dispatch(EventInterface $event): EventInterface {
        foreach ($this->provider->getListenersForEvent($event) as $listener) {
            if (!$event->isPropagationStopped()) {
                async($listener, $event)->await();
            }
        }
        return $event;
    }

    /**
     * @param class-string $eventName
     * @param callable $handler
     * @param bool $once
     * @return $this
     * @see EventDispatcherTest::testAddOnceEventListener()
     */
    public function addEventListener(string $eventName, callable $handler, bool $once = false): self {
        if ($once) {
            $this->provider->once($eventName, $handler);
        } else {
            $this->provider->on($eventName, $handler);
        }
        return $this;
    }

    /**
     * @param class-string $eventName
     * @param callable $handler
     * @return $this
     * @see EventDispatcherTest::testRemoveEventListener()
     */
    public function removeEventListener(string $eventName, callable $handler): self {
        $this->provider->off($eventName, $handler);
        return $this;
    }
}
