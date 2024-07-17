<?php

namespace TBank\Domain\Entity;

final class AmountEntity {
    /**
     * @param int $units
     * @param int $nano
     * @param ?string $currency
     */
    public function __construct(public int $units, public int $nano, public string|null $currency = null) {
    }

    public function get(): float {
        return $this->units + $this->nano / 1000000000;
    }
}
