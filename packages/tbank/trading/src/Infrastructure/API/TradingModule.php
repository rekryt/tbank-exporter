<?php

namespace TBank\Infrastructure\API;

use TBank\App\Event\SignalEvent;

use Monolog\Logger;
use Closure;
use TBank\Infrastructure\Storage\MainStorage;

class TradingModule implements AppModuleInterface {
    private static TradingModule $_instance;
    private Closure $handler;

    /**
     * @param ?Logger $logger
     */
    private function __construct(private ?Logger $logger) {
        $this->logger = ($logger ?? App::getLogger())->withName('TradingModule');
        $this->handler = function () {
            $this->logger->notice('working with signals', [MainStorage::getInstance()->get('signals')]);

            // todo trading logic
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
