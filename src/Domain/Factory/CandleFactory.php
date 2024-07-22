<?php

namespace TBank\Domain\Factory;

use TBank\Domain\Entity\CandleEntity;

final class CandleFactory {
    public static function create(object $data): CandleEntity {
        return new CandleEntity(
            figi: $data->figi ?? '',
            interval: $data->interval ?? '',
            open: AmountFactory::create($data->open ?? (object) []),
            high: AmountFactory::create($data->open ?? (object) []),
            low: AmountFactory::create($data->open ?? (object) []),
            close: AmountFactory::create($data->open ?? (object) []),
            volume: $data->volume ?? 0,
            time: $data->time ?? '',
            lastTradeTs: $data->lastTradeTs ?? '',
            instrumentUid: $data->instrumentUid ?? ''
        );
    }
}
