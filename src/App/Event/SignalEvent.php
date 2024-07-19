<?php

namespace TBank\App\Event;

use TBank\Domain\Entity\SignalEntity;

class SignalEvent extends AbstractEvent {
    public function __construct(public SignalEntity $signal) {
    }
}
