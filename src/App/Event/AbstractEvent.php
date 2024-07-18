<?php

namespace TBank\App\Event;

abstract class AbstractEvent implements EventInterface {
    private bool $propagation = true;

    public function isPropagationStopped(): bool {
        return !$this->propagation;
    }

    public function stopPropagation(): self {
        $this->propagation = false;
        return $this;
    }
}
