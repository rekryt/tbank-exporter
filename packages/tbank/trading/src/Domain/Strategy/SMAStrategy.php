<?php

namespace TBank\Domain\Strategy;

use Closure;
use Monolog\Logger;
use TBank\App\Event\SignalEvent;
use TBank\App\Service\PrometheusMetricsService;
use TBank\Infrastructure\API\App;
use TBank\Infrastructure\Storage\MainStorage;

/**
 * Стратегия заключение в выработке двух новых метрик ENTRY и CROSS
 * TradingModule на их основе создает новый сигнал EXACT
 *
 * SMA603010 - значение метрики полученное из prometheus
 * SMA603010_ENTRY: 0 - вне коридора точности, 1 - в коридоре точности
 * SMA603010_CROSS: 0 - пересечение нуля из минуса в плюс (продажа), 1 - пересечение нуля из плюса в минус (покупка)
 * SMA603010_EXACT: -1 - продавать, 0 - ждать, 1 - покупать
 *
 * SMA603010 метрики определяются в @see PrometheusMetricsService
 * SMA603010_ENTRY и SMA603010_CROSS определяются тут, в стратегии
 * SMA603010_EXACT в @see ExactStrategy
 */
final class SMAStrategy extends AbstractStrategy {
    private Closure $handler;
    private ?Logger $logger;
    private array $tickers = [];
    private MainStorage $storage;
    private float $precision = 0.05;

    public function __construct() {
        $this->logger = App::getLogger()->withName('SMAStrategy');
        $this->storage = MainStorage::getInstance();
        $this->handler = function (SignalEvent $event) {
            if (
                in_array(substr($event->signal->name, -6), ['_ENTRY', '_CROSS', '_EXACT']) ||
                !str_starts_with($event->signal->name, 'SMA')
            ) {
                return;
            }
            if (!isset($this->tickers[$event->signal->ticker])) {
                $this->tickers[$event->signal->ticker] = $event->signal->value;
                return;
            }

            if (!$this->storage->getSignal($event->signal->name . '_ENTRY', $event->signal->ticker)) {
                $this->storage->setSignal($event->signal->name . '_ENTRY', $event->signal->ticker, 0);
            }
            // если метрика входит в трубку точности
            // ENTRY = 1 (можно готовиться торговать)
            if (
                $event->signal->value <= $this->precision &&
                $event->signal->value >= -1 * $this->precision &&
                $this->storage->getSignal($event->signal->name . '_ENTRY', $event->signal->ticker)->value == 0
            ) {
                $this->storage->setSignal($event->signal->name . '_ENTRY', $event->signal->ticker, 1);
                $this->logger->notice('ENTRY IN', [$event]);
            }
            // если метрика выходит из трубки точности
            // ENTRY = 0 (ждём)
            if (
                ($event->signal->value > $this->precision || $event->signal->value < -1 * $this->precision) &&
                $this->storage->getSignal($event->signal->name . '_ENTRY', $event->signal->ticker)->value == 1
            ) {
                $this->storage->setSignal($event->signal->name . '_ENTRY', $event->signal->ticker, 0);
                $this->logger->notice('ENTRY OUT', [$event]);
            }

            if (!$this->storage->getSignal($event->signal->name . '_CROSS', $event->signal->ticker)) {
                $this->storage->setSignal($event->signal->name . '_CROSS', $event->signal->ticker, 0);
            }
            // если метрика меньше нуля, а прошлое значение сигнала этой метрики 0
            // пересекаем сверху CROSS = 1 (покупть)
            if (
                $event->signal->value < 0 &&
                $this->storage->getSignal($event->signal->name . '_CROSS', $event->signal->ticker)->value == 0
            ) {
                $this->storage->setSignal($event->signal->name . '_CROSS', $event->signal->ticker, 1);
                $this->logger->notice('CROSS IN', [$event]);
            }
            // если метрика больше нуля, а прошлое значение сигнала этой метрики 1
            // пересекаем сверху CROSS = 0 (продавать)
            if (
                $event->signal->value > 0 &&
                $this->storage->getSignal($event->signal->name . '_CROSS', $event->signal->ticker)->value == 1
            ) {
                $this->storage->setSignal($event->signal->name . '_CROSS', $event->signal->ticker, 0);
                $this->logger->notice('CROSS OUT', [$event]);
            }
        };
        parent::__construct($this->logger);
        $this->logger->notice('loaded');
    }

    function getHandler(): Closure {
        return $this->handler;
    }
}
