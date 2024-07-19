<?php

namespace TBank\Infrastructure\API;

use TBank\App\Event\SignalEvent;

use Monolog\Logger;
use Closure;
use TBank\Infrastructure\Storage\MainStorage;
use function TBank\dbg;
use function TBank\getEnv;

class TradingModule implements AppModuleInterface {
    private static TradingModule $_instance;
    private Closure $handler;
    private MainStorage $storage;

    private function getMetrics(string $name, string $ticker, string|int $value): string {
        return (getEnv('METRICS_SIGNAL') ?? 'signal') . '{ticker="' . $ticker . '",name="' . $name . '"} ' . $value;
    }

    /**
     * @param ?Logger $logger
     */
    private function __construct(private ?Logger $logger) {
        $this->logger = ($logger ?? App::getLogger())->withName('TradingModule');
        $this->storage = MainStorage::getInstance();
        $this->handler = function (SignalEvent $event) {
            $signals = $this->storage->getSignals();
            $this->logger->notice('working with signals', [$event]);

            $isEntry = str_ends_with($event->signal->name, '_ENTRY');
            $isCross = str_ends_with($event->signal->name, '_CROSS');
            if ($isEntry || $isCross) {
                $shortName = substr($event->signal->name, 0, strlen($event->signal->name) - 6);
                $exactSignalValue = null;
                // начальное значение ENTRY
                if (!isset($signals[$shortName . '_ENTRY:' . $event->signal->ticker])) {
                    $signals[$shortName . '_ENTRY:' . $event->signal->ticker]->value = '0';
                }
                // начальное значение EXACT
                if (!isset($signals[$shortName . '_EXACT:' . $event->signal->ticker])) {
                    $exactSignalValue = 0;
                    $signals[$shortName . '_EXACT:' . $event->signal->ticker]->value = '0';
                }
                // если мы выходим из трубки точности
                if ($isEntry && !$event->signal->value) {
                    $exactSignalValue = 0;
                }
                // если мы пересекаем ноль находясь в трубке точности
                if ($isCross && $signals[$shortName . '_ENTRY:' . $event->signal->ticker]->value == '1') {
                    // и если мы в состоянии ожидания
                    if ($signals[$shortName . '_EXACT:' . $event->signal->ticker]->value == '0') {
                        $exactSignalValue = $event->signal->value ? 1 : -1;
                    } else {
                        // если мы не в состоянии ожидание но пересекаем 0, значит это дребезг в трубке точности, надо ждать
                        $exactSignalValue = 0;
                    }
                }
                if (!is_null($exactSignalValue)) {
                    $signals[$shortName . '_EXACT:' . $event->signal->ticker]->value = $exactSignalValue;
                    $this->storage->setSignals($signals);
                }
            }
        };
    }

    /**
     * @param ?Logger $logger
     * @return TradingModule
     */
    public static function getInstance(Logger $logger = null): TradingModule {
        return self::$_instance ??= new self($logger);
    }

    public function start(): void {
        App::getInstance()
            ->getDispatcher()
            ->addEventListener(SignalEvent::class, $this->handler);
        $this->logger->info('started');
    }

    public function stop(): void {
        App::getInstance()
            ->getDispatcher()
            ->removeEventListener(SignalEvent::class, $this->handler);
        $this->logger->info('stopped');
    }
}
