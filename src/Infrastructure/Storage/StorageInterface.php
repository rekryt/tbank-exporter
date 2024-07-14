<?php

namespace TBank\Infrastructure\Storage;

interface StorageInterface {
    public function get(string $key): float;
    public function set(string $key, float $value): bool;
}
