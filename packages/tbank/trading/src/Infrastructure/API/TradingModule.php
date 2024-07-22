<?php

namespace TBank\Infrastructure\API;

use Monolog\Logger;
use TBank\Infrastructure\Storage\MainStorage;
use function TBank\getEnv;

class TradingModule extends AbstractAppStrategyModule {
    private static TradingModule $_instance;
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
    }

    /**
     * @param ?Logger $logger
     * @return TradingModule
     */
    public static function getInstance(Logger $logger = null): TradingModule {
        return self::$_instance ??= new self($logger);
    }

    public function start(): void {
        $this->logger->info('started');
    }

    public function stop(): void {
        $this->strategies = [];
        $this->logger->info('stopped');
    }
}
