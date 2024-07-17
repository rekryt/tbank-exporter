<?php

namespace TBank\Infrastructure\Storage;

use TBank\Domain\Entity\OrderEntity;

final class OrdersStorage implements StorageInterface {
    private static OrdersStorage $_instance;

    private array $data = [];

    private function __construct() {
    }

    public static function getInstance(): OrdersStorage {
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

    /**
     * @param string $orderId
     * @return ?OrderEntity
     */
    public function getByOrderId(string $orderId): OrderEntity|null {
        $search = array_filter($this->data, fn(OrderEntity $order) => $order->orderId === $orderId);
        return count($search) ? $search[0] : null;
    }
}
