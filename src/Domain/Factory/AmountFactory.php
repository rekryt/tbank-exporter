<?php

namespace TBank\Domain\Factory;

use TBank\Domain\Entity\AmountEntity;

final class AmountFactory {
    public static function create(object $data): AmountEntity {
        return new AmountEntity(units: $data->units ?? 0, nano: $data->nano ?? 0, currency: $data->currency ?? null);
    }
}
