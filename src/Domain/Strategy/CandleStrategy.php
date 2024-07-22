<?php

namespace TBank\Domain\Strategy;

use Closure;
use Monolog\Logger;
use TBank\App\Event\CandleEvent;
use TBank\App\Event\SignalEvent;
use TBank\Infrastructure\API\App;
use TBank\Infrastructure\Storage\InstrumentsStorage;
use TBank\Infrastructure\Storage\MainStorage;

use function TBank\dbg;
use function TBank\getEnv;

/**
 * Стратегия заключение в выработке метрики volume (обьём продаж) в шт бумаг
 *
 * @see InstrumentsStorage::setCandle()
 */
final class CandleStrategy extends AbstractStrategy {
    private Closure $handler;
    private ?Logger $logger;
    private MainStorage $storage;

    public function __construct() {
        $this->logger = App::getLogger()->withName('CandleStrategy');
        parent::__construct($this->logger);

        $this->storage = MainStorage::getInstance();
        $this->dispather->addEventListener(CandleEvent::class, $this->candleHandler(...));

        $this->logger->notice('loaded');
    }

    public function __destruct() {
        parent::__destruct();
        $this->dispather->removeEventListener(CandleEvent::class, $this->candleHandler(...));
    }

    public function candleHandler(CandleEvent $event): void {
        if ($instrument = $this->storage->getTicker($event->candle->instrumentUid)) {
//            $this->storage->setSignal(
//                \getEnv('METRICS_VOLUME') ?? 'volume',
//                $instrument->ticker,
//                $event->candle->volume * $instrument->lot
//            );
        }
    }
}
