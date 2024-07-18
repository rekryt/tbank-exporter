<?php

namespace TBank\Infrastructure\API\EventDispatcher;

use TBank\App\Event\EventInterface;
use function md5;

final class ListenerProvider implements ListenerProviderInterface {
    /**
     * @var array
     */
    private array $listeners = [];

    /**
     * @param EventInterface $event
     * @return iterable
     */
    public function getListenersForEvent(EventInterface $event): iterable {
        return $this->listeners[md5($event::class)] ?? [];
    }

    /**
     * @param class-string $eventName
     * @param callable $handler
     * @return $this
     */
    public function on(string $eventName, callable $handler): self {
        $this->listeners[md5($eventName)][] = $handler;
        return $this;
    }

    /**
     * @param class-string $eventName
     * @param callable $handler
     * @return $this
     */
    public function once(string $eventName, callable $handler): self {
        $wrappedHandler = function (...$args) use ($eventName, $handler, &$wrappedHandler) {
            $this->off($eventName, $wrappedHandler);
            $handler(...$args);
        };

        $this->on($eventName, $wrappedHandler);

        return $this;
    }

    /**
     * @param class-string $eventName
     * @param callable $handler
     * @return $this
     */
    public function off(string $eventName, callable $handler): self {
        $eventHash = md5($eventName);
        if (!isset($this->listeners[$eventHash])) {
            return $this;
        }

        foreach ($this->listeners[$eventHash] as $key => $listener) {
            if ($handler === $listener) {
                array_splice($this->listeners[$eventHash], $key, 1);
                break;
            }
        }

        return $this;
    }
}
