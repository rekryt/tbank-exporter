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

    /**
     * @param ?Logger $logger
     */
    private function __construct(private ?Logger $logger) {
        $this->logger = ($logger ?? App::getLogger())->withName('TradingModule');
        $this->storage = MainStorage::getInstance();
        $this->handler = function (SignalEvent $event) {
            $signals = $this->storage->get('signals');
            $this->logger->notice('working with signals', [$event]);

            if (str_ends_with($event->signalName, '_ENTRY') && !$event->value) {
                $crossSignalName =
                    substr($event->signalName, 0, strlen($event->signalName) - 6) . '_CROSS:' . $event->ticker;
                $signals[$crossSignalName] =
                    (getEnv('METRICS_SIGNAL') ?? 'signal') .
                    '{ticker="' .
                    $event->ticker .
                    '",name="' .
                    $event->signalName .
                    '"} 0';

                $this->storage->set('signals', $signals);
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
