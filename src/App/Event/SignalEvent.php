<?php

namespace TBank\App\Event;

class SignalEvent extends AbstractEvent {
    public function __construct(public string $signalName, public string $ticker, public string|int $value) {
    }
}
