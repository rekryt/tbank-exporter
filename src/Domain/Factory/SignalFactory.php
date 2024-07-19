<?php

namespace TBank\Domain\Factory;

use TBank\Domain\Entity\SignalEntity;

final class SignalFactory {
    public static function create(object $data): SignalEntity {
        return new SignalEntity(ticker: $data->ticker, name: $data->name, value: $data->value);
    }
}
