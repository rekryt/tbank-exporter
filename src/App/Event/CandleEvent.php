<?php

namespace TBank\App\Event;

use TBank\Domain\Entity\CandleEntity;
use TBank\Infrastructure\Storage\InstrumentsStorage;

/**
 * Событие вызывается когда приходит новая свеча по инструменту
 * в @see InstrumentsStorage::setCandle()
 */
class CandleEvent extends AbstractEvent {
    public function __construct(public CandleEntity $candle) {
    }
}
