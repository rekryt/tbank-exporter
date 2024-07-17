<?php

namespace TBank\Infrastructure\Storage;

final class MainStorage implements StorageInterface {
    private static MainStorage $_instance;

    private array $data = [];

    private function __construct() {
    }

    public static function getInstance(): MainStorage {
        return self::$_instance ??= new self();
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
}
