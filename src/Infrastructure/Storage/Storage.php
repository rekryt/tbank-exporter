<?php

namespace TBank\Infrastructure\Storage;

use Revolt\EventLoop;
use TBank\Infrastructure\API\Server;

final class Storage implements StorageInterface {
    private static Storage $_instance;

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

    public static function getInstance(): Storage {
        return self::$_instance ??= new self();
    }

    private function save(): void {
        Server::getLogger()->notice('Background saving');
        file_put_contents(PATH_ROOT . '/' . $this->filename, json_encode($this->data));
    }

    public function get(string $key): float {
        return $this->data[$key];
    }

    public function set(string $key, float $value): bool {
        $this->data[$key] = $value;
        return true;
    }

    /**
     * @return array
     */
    public function getData(): array {
        return $this->data;
    }
}
