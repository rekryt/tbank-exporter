<?php

namespace TBank\Domain\Entity;

final class SignalEntity {
    public function __construct(public string $ticker, public string $name, public float $value) {
    }
}
