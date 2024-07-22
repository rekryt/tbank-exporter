<?php

namespace TBank\Domain\Entity;

final class SignalEntity {
    /**
     * @param string $ticker тикер
     * @param string $name имя метрики
     * @param float $value значение
     */
    public function __construct(public string $ticker, public string $name, public float $value) {
    }
}
