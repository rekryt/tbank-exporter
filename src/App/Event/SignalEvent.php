<?php

namespace TBank\App\Event;

use TBank\Domain\Entity\SignalEntity;
use TBank\Infrastructure\Storage\MainStorage;

/**
 * Событие вызывается при изменении сигнала
 * в @see MainStorage::setSignals()
 */
class SignalEvent extends AbstractEvent {
    public function __construct(public SignalEntity $signal) {
    }
}
