<?php

namespace TBank\Infrastructure\API\EventDispatcher;

use TBank\App\Event\EventInterface;

/**
 * Defines a dispatcher for events.
 */
interface EventDispatcherInterface {
    /**
     * Provide all relevant listeners with an event to process.
     *
     * @param EventInterface $event
     *   The object to process.
     *
     * @return EventInterface
     *   The Event that was passed, now modified by listeners.
     */
    public function dispatch(EventInterface $event): EventInterface;
}
