<?php

namespace TBank\App\Event;

use TBank\App\Service\AbstractStreamService;

/**
 * Событие вызывается при получении WS сообщения
 * в @see AbstractStreamService::connect()
 */
class StreamEvent extends AbstractEvent {
    public function __construct(public object $payload) {
    }
}
