<?php

namespace TBank\Infrastructure\Storage;

use Revolt\EventLoop;
use TBank\App\Event\CandleEvent;
use TBank\Domain\Entity\CandleEntity;
use TBank\Domain\Entity\InstrumentEntity;
use TBank\Domain\Factory\CandleFactory;
use TBank\Infrastructure\API\App;
use TBank\Infrastructure\API\Server;

final class InstrumentsStorage implements StorageInterface {
    private static InstrumentsStorage $_instance;

    /**
     * @var float[]
     */
    private array $prices = [];

    /**
     * @var CandleEntity[]
     */
    private array $candles = [];

    private string $filenamePrices = 'storage.json';
    private string $filenameCandles = 'storage-candles.json';

    private function __construct() {
        $this->prices = is_file($file = PATH_ROOT . '/storage/' . $this->filenamePrices)
            ? (array) json_decode(file_get_contents($file)) ?? []
            : [];

        $this->candles = is_file($file = PATH_ROOT . '/storage/' . $this->filenameCandles)
            ? array_map(fn($data) => CandleFactory::create($data), (array) json_decode(file_get_contents($file)) ?? [])
            : [];

        EventLoop::repeat(60, fn() => $this->save());
    }

    /**
     * @return InstrumentsStorage
     */
    public static function getInstance(): InstrumentsStorage {
        return self::$_instance ??= new self();
    }

    private function save(): void {
        $logger = Server::getLogger()->withName('InstrumentsStorage');
        $logger->notice('Background saving');
        file_put_contents(PATH_ROOT . '/storage/' . $this->filenamePrices, json_encode($this->prices));
        file_put_contents(PATH_ROOT . '/storage/' . $this->filenameCandles, json_encode($this->candles));
    }

    public function get(string $key): InstrumentEntity {
        return $this->prices[$key];
    }

    public function set(string $key, mixed $value): bool {
        $this->prices[$key] = $value;
        return true;
    }

    /**
     * @return array<InstrumentEntity>
     */
    public function getData(): array {
        return $this->prices;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool {
        return isset($this->prices[$key]);
    }

    /**
     * @param CandleEntity $candle
     */
    public function setCandle(CandleEntity $candle): void {
        $this->candles[$candle->figi] = $candle;
        App::getInstance()
            ->getDispatcher()
            ->dispatch(new CandleEvent($candle));
    }

    /**
     * @return array<CandleEntity>
     */
    public function getCandles(): array {
        return $this->candles;
    }
}
