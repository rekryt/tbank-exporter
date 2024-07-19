<?php

namespace TBank\Infrastructure\Storage;

use Revolt\EventLoop;
use TBank\App\Event\SignalEvent;
use TBank\Infrastructure\API\App;
use TBank\Infrastructure\API\Server;

final class MainStorage implements StorageInterface {
    private static MainStorage $_instance;

    private array $data = [
        'account' => null,
        'portfolio' => null,
        'tickers' => [],
        'signals' => [],
    ];

    private string $filename = 'storage-main.json';

    private function __construct() {
        $file = PATH_ROOT . '/storage/' . $this->filename;
        if (is_file($file)) {
            $this->data = (array) json_decode(file_get_contents($file)) ?? [
                'account' => null,
                'portfolio' => null,
                'tickers' => [],
                'signals' => [],
            ];
            $this->data['tickers'] = (array) $this->data['tickers'];
            $this->data['signals'] = (array) $this->data['signals'];
        } else {
            $this->data = ['account' => null, 'portfolio' => null, 'tickers' => [], 'signals' => []];
        }

        EventLoop::repeat(60, fn() => $this->save());
    }

    public static function getInstance(): MainStorage {
        return self::$_instance ??= new self();
    }

    public function get(string $key): mixed {
        return $this->data[$key];
    }

    public function set(string $key, mixed $value): bool {
        $this->data[$key] = $value;
        if ($key == 'signals') {
            App::getInstance()
                ->getDispatcher()
                ->dispatch(new SignalEvent());
        }
        return true;
    }

    /**
     * @return array
     */
    public function getData(): array {
        return $this->data;
    }

    public function setData(array $data): void {
        $this->data = $data;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool {
        return isset($this->data[$key]);
    }

    private function save(): void {
        $logger = Server::getLogger()->withName('MainStorage');
        $logger->notice('Background saving');
        file_put_contents(PATH_ROOT . '/storage/' . $this->filename, json_encode($this->data));
    }
}
