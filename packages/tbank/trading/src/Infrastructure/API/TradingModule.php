<?php

namespace TBank\Infrastructure\API;

use TBank\App\Event\SignalEvent;

use Monolog\Logger;
use Closure;
use TBank\Domain\Strategy\ExactStrategy;
use TBank\Domain\Strategy\SMAStrategy;
use TBank\Infrastructure\Storage\MainStorage;
use function TBank\dbg;
use function TBank\getEnv;

class TradingModule implements AppModuleInterface {
    private static TradingModule $_instance;
    private MainStorage $storage;
    private array $strategies;

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
        // регистрация стратегий (обработчики событий)
        $this->strategies = [new SMAStrategy(), new ExactStrategy()];
        $this->logger->info('started');
    }

    public function stop(): void {
        $this->strategies = [];
        $this->logger->info('stopped');
    }
}
