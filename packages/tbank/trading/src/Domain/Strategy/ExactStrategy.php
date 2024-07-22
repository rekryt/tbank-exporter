<?php

namespace TBank\Domain\Strategy;

use Closure;
use Monolog\Logger;
use TBank\App\Event\AbstractEvent;
use TBank\App\Event\SignalEvent;
use TBank\App\Service\PrometheusMetricsService;
use TBank\Infrastructure\API\App;
use TBank\Infrastructure\Storage\MainStorage;

/**
 * Стратегия заключение в выработке метрики EXACT
 *
 * SMA603010 - значение метрики полученное из prometheus
 * SMA603010_ENTRY: 0 - вне коридора точности, 1 - в коридоре точности
 * SMA603010_CROSS: 0 - пересечение нуля из минуса в плюс (продажа), 1 - пересечение нуля из плюса в минус (покупка)
 * SMA603010_EXACT: -1 - продавать, 0 - ждать, 1 - покупать
 *
 * SMA603010 метрики определяются в @see PrometheusMetricsService
 * SMA603010_ENTRY и SMA603010_CROSS в @see SMAStrategy
 * SMA603010_EXACT определяются тут, в стратегии
 */
final class ExactStrategy extends AbstractStrategy {
    private Closure $handler;
    private ?Logger $logger;
    private MainStorage $storage;

    public function __construct() {
        $this->logger = App::getLogger()->withName('ExactStrategy');
        parent::__construct($this->logger);

        $this->storage = MainStorage::getInstance();
        $this->dispather->addEventListener(SignalEvent::class, $this->signalHandler(...));

        $this->logger->notice('loaded');
    }

    public function __destruct() {
        parent::__destruct();
        $this->dispather->removeEventListener(SignalEvent::class, $this->signalHandler(...));
    }

    public function signalHandler(SignalEvent $event): void {
        $isEntry = str_ends_with($event->signal->name, '_ENTRY');
        $isCross = str_ends_with($event->signal->name, '_CROSS');
        if ($isEntry || $isCross) {
            $shortName = substr($event->signal->name, 0, strlen($event->signal->name) - 6);
            $exactSignalValue = null;
            // начальное значение EXACT
            if (!$this->storage->getSignal($shortName . '_EXACT', $event->signal->ticker)) {
                $exactSignalValue = 0;
                $this->storage->setSignal($shortName . '_EXACT', $event->signal->ticker, 0);
            }
            // если мы выходим из трубки точности
            if ($isEntry && !$event->signal->value) {
                $exactSignalValue = 0;
            }
            // если мы пересекаем ноль находясь в трубке точности
            if ($isCross && $this->storage->getSignal($shortName . '_ENTRY', $event->signal->ticker)->value == '1') {
                // и если мы в состоянии ожидания
                if ($this->storage->getSignal($shortName . '_EXACT', $event->signal->ticker)->value == '0') {
                    $exactSignalValue = $event->signal->value ? 1 : -1;
                } else {
                    // если мы не в состоянии ожидание но пересекаем 0, значит это дребезг в трубке точности, надо ждать
                    $exactSignalValue = 0;
                }
            }

            $signal = $this->storage->setSignal($shortName . '_EXACT', $event->signal->ticker, $exactSignalValue ?? 0);
            $states = [
                '-1' => 'SELL',
                '0' => 'WAIT',
                '1' => 'BUY',
            ];
            $this->logger->notice('EXACT ' . $states[$exactSignalValue ?? 0], [$signal]);
        }
    }
}
