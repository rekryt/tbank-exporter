<?php

namespace TBank\Infrastructure\Storage;

use Revolt\EventLoop;
use TBank\Infrastructure\API\Server;

final class InstrumentsStorage implements StorageInterface {
    private static InstrumentsStorage $_instance;

    /**
     * @var float[]
     */
    private array $data;

    private string $filename = 'storage.json';

    private function __construct() {
        $file = PATH_ROOT . '/' . $this->filename;
        if (is_file($file)) {
            $this->data = (array) json_decode(file_get_contents($file)) ?? [];
        } else {
            $this->data = [];
        }

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
        file_put_contents(PATH_ROOT . '/' . $this->filename, json_encode($this->data));
    }

    public function get(string $key): mixed {
        return $this->data[$key];
    }

    public function set(string $key, mixed $value): bool {
        $this->data[$key] = $value;
        return true;
    }

    /**
     * @return array
     */
    public function getData(): array {
        return $this->data;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool {
        return isset($this->data[$key]);
    }
}
